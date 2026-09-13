// medical-file-validator.js
class MedicalFileValidator {
    constructor(config = {}) {
        this.config = {
            maxFiles: 5,
            maxFileSize: 10 * 1024 * 1024, // 10MB
            maxPdfPages: 100,
            minImageWidth: 300,
            minImageHeight: 300,
            minImageContrast: 5,
            // ✅ AGREGAR DICOM
            allowedMimes: [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/dicom',  // DICOM
                'image/dicom',         // Variante
                'application/octet-stream' // DICOM a veces se envía así
            ],
            allowedExtensions: [
                'pdf',
                'jpg', 'jpeg',
                'png',
                'gif',
                'webp',
                'dcm',  // ✅ DICOM
                'dicom'
            ],
            ...config
        };

        this.uploadInProgress = false;
        this.processedFiles = new Map();
        this.errors = [];
        this.warnings = [];
    }

    /**
     * VALIDAR NOMBRE DE ARCHIVO (prevenir inyecciones)
     */
    validateFileName(fileName) {
        // ❌ RECHAZAR ZIP DIRECTAMENTE
        if (fileName.toLowerCase().endsWith('.zip')) {
            return {
                valid: false,
                error: 'No se permiten archivos comprimidos. Por favor descomprime el ZIP y sube los archivos directamente.'
            };
        }

        // Detectar caracteres maliciosos
        const dangerousChars = /[<>:"|?*\x00-\x1f]/g;
        if (dangerousChars.test(fileName)) {
            return {
                valid: false,
                error: 'nombre contiene caracteres no permitidos'
            };
        }

        // Detectar path traversal
        if (fileName.includes('../') || fileName.includes('..\\')) {
            return {
                valid: false,
                error: 'nombre de archivo inválido'
            };
        }

        // Detectar extensiones ejecutables
        const executableExts = ['exe', 'bat', 'cmd', 'sh', 'ps1', 'vbs'];
        const fileExt = fileName.split('.').pop().toLowerCase();
        if (executableExts.includes(fileExt)) {
            return {
                valid: false,
                error: 'extensión no permitida'
            };
        }

        return { valid: true };
    }

    /**
     * VALIDAR MIME TYPE (no confiar en extensión)
     * Ahora soporta DICOM
     */
    validateMimeType(file) {
        const fileName = file.name.toLowerCase();
        const fileExt = fileName.split('.').pop();

        // ✅ VALIDAR DICOM especial
        if (['dcm', 'dicom'].includes(fileExt)) {
            // Para DICOM, el MIME type puede variar, así que validar por extensión
            return { valid: true };
        }

        // Para otros tipos, validar MIME
        if (!this.config.allowedMimes.includes(file.type)) {
            return {
                valid: false,
                error: `tipo de archivo no soportado (${file.type}). Usa PDF, JPG, PNG o DICOM (.dcm).`
            };
        }

        // Validar que la extensión coincida con MIME
        const mimeToExt = {
            'application/pdf': ['pdf'],
            'image/jpeg': ['jpg', 'jpeg'],
            'image/png': ['png'],
            'image/gif': ['gif'],
            'image/webp': ['webp'],
            'application/dicom': ['dcm', 'dicom'],
            'image/dicom': ['dcm', 'dicom'],
            'application/octet-stream': ['dcm', 'dicom'] // DICOM puede venir así
        };

        const expectedExts = mimeToExt[file.type] || [];
        if (expectedExts.length > 0 && !expectedExts.includes(fileExt)) {
            return {
                valid: false,
                error: `extensión (.${fileExt}) no coincide con tipo de archivo (${file.type})`
            };
        }

        return { valid: true };
    }

    /**
     * VALIDAR CONTENIDO SEGÚN TIPO
     */
    async validateSingleFile(file) {
        const errors = [];
        const warnings = [];

        // 1️⃣ Validar nombre de archivo (sanitizar) - AQUÍ RECHAZA ZIP
        const nameValidation = this.validateFileName(file.name);
        if (!nameValidation.valid) {
            errors.push(`${file.name}: ${nameValidation.error}`);
            return { valid: false, errors };
        }

        // 2️⃣ Validar tamaño
        if (file.size === 0) {
            errors.push(`${file.name}: archivo vacío`);
            return { valid: false, errors };
        }

        if (file.size > this.config.maxFileSize) {
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            const maxMB = (this.config.maxFileSize / 1024 / 1024).toFixed(0);
            errors.push(`${file.name}: ${sizeMB}MB excede el máximo de ${maxMB}MB`);
            return { valid: false, errors };
        }

        // 3️⃣ Validar extensión y MIME type
        const mimeValidation = this.validateMimeType(file);
        if (!mimeValidation.valid) {
            errors.push(`${file.name}: ${mimeValidation.error}`);
            return { valid: false, errors };
        }

        // 4️⃣ Detectar duplicados por hash
        const hash = await this.calculateFileHash(file);
        if (this.processedFiles.has(hash)) {
            warnings.push(`${file.name}: parece ser un duplicado de ${this.processedFiles.get(hash)}`);
        } else {
            this.processedFiles.set(hash, file.name);
        }

        // 5️⃣ Validar contenido según tipo
        const fileExt = file.name.split('.').pop().toLowerCase();

        // ✅ DICOM - solo avisar que se convertirá en backend
        if (['dcm', 'dicom'].includes(fileExt)) {
            warnings.push(`${file.name}: archivo DICOM será convertido a imagen en el servidor`);
            return { valid: true, warnings };
        }

        // PDF
        if (file.type === 'application/pdf') {
            const pdfValidation = await this.validatePDF(file);
            if (!pdfValidation.valid) {
                errors.push(...pdfValidation.errors);
                return { valid: false, errors };
            }
            if (pdfValidation.warnings) {
                warnings.push(...pdfValidation.warnings);
            }
        }
        // Imágenes
        else if (file.type.startsWith('image/')) {
            const imageValidation = await this.validateImage(file);
            if (!imageValidation.valid) {
                errors.push(...imageValidation.errors);
                return { valid: false, errors };
            }
            if (imageValidation.warnings) {
                warnings.push(...imageValidation.warnings);
            }
        }

        return {
            valid: errors.length === 0,
            errors,
            warnings
        };
    }

    setupEventListeners() {
        // Drag & drop
        this.dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone.addEventListener('drop', (e) => this.handleDrop(e));
        
        // Input file
        const fileInput = this.form.querySelector('input[type="file"]');
        fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
    }

    handleFileSelect(event) {
        const files = event.target.files;
        Array.from(files).forEach(file => this.analyzeFile(file));
    }

    handleDragOver(event) {
        event.preventDefault();
        this.dropZone.classList.add('drag-active');
    }

    handleDrop(event) {
        event.preventDefault();
        this.dropZone.classList.remove('drag-active');
        
        const files = event.dataTransfer.files;
        Array.from(files).forEach(file => this.analyzeFile(file));
    }

    analyzeFile(file) {
        const validation = this.validateFile(file);
        
        if (!validation.isValid) {
            this.showError(validation.errorMessage);
            return;
        }

        // 🎯 LÓGICA CLAVE: Detectar tipo
        if (validation.isImaging) {
            this.switchToImagingFlow(file, validation);
        } else {
            this.switchToLabFlow(file, validation);
        }

        // Mostrar preview
        this.showFilePreview(file, validation);
    }

    validateFile(file) {
        const mime = file.type;
        const name = file.name.toLowerCase();
        const size = file.size;
        const maxSize = 10 * 1024 * 1024; // 10MB

        // Validaciones básicas
        if (size > maxSize) {
            return {
                isValid: false,
                errorMessage: `Archivo muy grande. Máximo 10MB (${(size / 1024 / 1024).toFixed(1)}MB)`
            };
        }

        const supportedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!supportedMimes.includes(mime)) {
            return {
                isValid: false,
                errorMessage: `Formato no soportado: ${mime}`
            };
        }

        // 🔍 DETECCIÓN INTELIGENTE
        const imagingKeywords = [
            'radiografia', 'radiografía', 'rx', 'xray', 'x-ray',
            'tomografia', 'tomografía', 'ct', 'scan',
            'resonancia', 'mri', 'rmn',
            'ecografia', 'ecografía', 'ultrasound', 'eco',
            'mamografia', 'mamografía',
            'angiografia', 'angiografía'
        ];

        const hasImagingKeyword = imagingKeywords.some(keyword => name.includes(keyword));
        const isImage = ['image/jpeg', 'image/png'].includes(mime);

        return {
            isValid: true,
            isImaging: (isImage && hasImagingKeyword) || (isImage && !name.includes('resultado') && !name.includes('laboratorio')),
            isLab: mime === 'application/pdf' || name.includes('laboratorio') || name.includes('examen'),
            fileType: mime,
            fileName: file.name,
            fileSize: size,
            fileObject: file
        };
    }

    switchToImagingFlow(file, validation) {
        this.detectedType = 'imaging';
        console.log('📸 Detectado: IMAGENOLOGÍA', validation.fileName);

        // Cambiar UI
        this.updateUIForType('imaging');
        
        // Cambiar la acción del form
        this.form.action = '/medical-analysis/imaging'; // Nueva ruta
        
        // Agregar campo hidden con el tipo
        this.setFormField('analysis_type', 'imaging');
        
        // Cambiar campos dinámicos
        this.updateDynamicFields('imaging');
    }

    switchToLabFlow(file, validation) {
        this.detectedType = 'lab';
        console.log('🧪 Detectado: LABORATORIO', validation.fileName);

        // Cambiar UI
        this.updateUIForType('lab');
        
        // Ruta original
        this.form.action = '/medical-analysis/upload';
        
        // Agregar campo hidden con el tipo
        this.setFormField('analysis_type', 'lab');
        
        // Cambiar campos dinámicos
        this.updateDynamicFields('lab');
    }

    updateUIForType(type) {
        const heading = document.querySelector('h1');
        const subheading = document.querySelector('.file-description');
        const submitBtn = document.querySelector('button[type="submit"]');

        if (type === 'imaging') {
            heading.textContent = '🩻 Análisis de Imagenología por IA';
            subheading.textContent = 'Radiografía, Tomografía, Resonancia Magnética, Ecografía';
            submitBtn.classList.add('btn-imaging');
            submitBtn.classList.remove('btn-lab');
        } else {
            heading.textContent = '🧪 Análisis de Laboratorio por IA';
            subheading.textContent = 'Reportes de exámenes de sangre, bioquímica, hematología';
            submitBtn.classList.add('btn-lab');
            submitBtn.classList.remove('btn-imaging');
        }
    }

    updateDynamicFields(type) {
        const reasonSelect = document.querySelector('select[name="reason"]');
        const detailsField = document.querySelector('textarea[name="details"]');

        if (type === 'imaging') {
            // Para imagenología, agregar campo de modalidad
            const modalityField = `
                <div class="form-group mt-3">
                    <label>Tipo de estudio:</label>
                    <select name="modality" class="form-control">
                        <option value="">Selecciona...</option>
                        <option value="xray">Radiografía (RX)</option>
                        <option value="ct">Tomografía Computada (CT)</option>
                        <option value="mri">Resonancia Magnética (MRI)</option>
                        <option value="ultrasound">Ecografía</option>
                        <option value="mamography">Mamografía</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
            `;
            
            const existingModality = document.querySelector('[name="modality"]');
            if (!existingModality) {
                reasonSelect.parentElement.insertAdjacentHTML('afterend', modalityField);
            }

            detailsField.placeholder = 'Ej: Dolor en pecho, después de accidente, síntomas respiratorios...';
        } else {
            // Para lab, remover campo de modalidad si existe
            const modalityField = document.querySelector('[name="modality"]')?.parentElement;
            if (modalityField) modalityField.remove();

            detailsField.placeholder = 'Ej: Diabetes, presión alta, medicamentos que toma...';
        }
    }

    setFormField(name, value) {
        let field = document.querySelector(`input[name="${name}"]`);
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            this.form.appendChild(field);
        }
        field.value = value;
    }

    showFilePreview(file, validation) {
        const previewContainer = document.querySelector('.file-preview');
        if (!previewContainer) return;

        const preview = `
            <div class="file-item ${validation.isImaging ? 'imaging' : 'lab'}">
                <span class="icon">${validation.isImaging ? '📸' : '📄'}</span>
                <div>
                    <p class="file-name">${file.name}</p>
                    <p class="file-type">${validation.isImaging ? 'Imagenología' : 'Laboratorio'}</p>
                </div>
                <span class="file-size">${(file.size / 1024).toFixed(0)}KB</span>
            </div>
        `;

        previewContainer.insertAdjacentHTML('beforeend', preview);
    }

    showError(message) {
        alert(`⚠️ ${message}`);
    }
}

// Inicializar cuando carga el DOM
document.addEventListener('DOMContentLoaded', () => {
    new MedicalFileValidator('.drop-zone', 'form[name="medical-analysis"]');
});