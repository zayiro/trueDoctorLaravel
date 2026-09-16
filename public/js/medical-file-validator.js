/**
 * MedicalFileValidator - Validador de archivos médicos para OpenDoctor
 * Valida tipos, tamaños y cantidad de archivos médicos
 */
class MedicalFileValidator {
    constructor() {
        this.MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB en bytes
        this.MAX_FILES = 5;
        this.ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'dcm', 'dicom'];
        this.ALLOWED_MIME_TYPES = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/dicom',
            'image/dicom'
        ];
    }

    /**
     * Validar un conjunto de archivos
     * @param {FileList} files - Lista de archivos a validar
     * @returns {Promise<Object>} Objeto con resultado de validación
     */
    async validateFiles(files) {
        const result = {
            valid: true,
            validFiles: [],
            rejectedFiles: []
        };

        // Validar cantidad máxima de archivos
        if (files.length > this.MAX_FILES) {
            result.valid = false;
            result.rejectedFiles.push({
                name: 'Cantidad de archivos',
                errors: [`Máximo ${this.MAX_FILES} archivos permitidos. Seleccionaste ${files.length}.`]
            });
            return result;
        }

        // Validar cada archivo
        for (let file of files) {
            const validation = this.validateFile(file);

            if (validation.valid) {
                result.validFiles.push({
                    file: file,
                    warnings: validation.warnings || []
                });
            } else {
                result.rejectedFiles.push({
                    name: file.name,
                    errors: validation.errors || []
                });
                result.valid = false;
            }
        }

        return result;
    }

    /**
     * Validar un archivo individual
     * @param {File} file - Archivo a validar
     * @returns {Object} Objeto con resultado de validación
     */
    validateFile(file) {
        const result = {
            valid: true,
            errors: [],
            warnings: []
        };

        // 1. Validar extensión
        const extension = this.getFileExtension(file.name).toLowerCase();
        if (!this.ALLOWED_EXTENSIONS.includes(extension)) {
            result.valid = false;
            result.errors.push(`Formato no permitido. Solo se aceptan: ${this.ALLOWED_EXTENSIONS.join(', ').toUpperCase()}`);
        }

        // 2. Validar tipo MIME
        if (!this.isMimeTypeAllowed(file.type)) {
            result.warnings.push('El tipo MIME no se reconoce completamente, pero continuaremos.');
        }

        // 3. Validar tamaño
        if (file.size > this.MAX_FILE_SIZE) {
            result.valid = false;
            const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
            result.errors.push(`Archivo muy grande (${sizeInMB}MB). Máximo permitido: 10MB`);
        }

        // 4. Validar tamaño mínimo (al menos 1KB)
        if (file.size < 1024) {
            result.valid = false;
            result.errors.push('El archivo es demasiado pequeño o está vacío.');
        }

        // 5. Advertencias adicionales
        if (extension === 'pdf' && file.size > 5 * 1024 * 1024) {
            result.warnings.push('PDF grande: puede tardar más en procesar');
        }

        return result;
    }

    /**
     * Obtener extensión del archivo
     * @param {string} filename - Nombre del archivo
     * @returns {string} Extensión sin punto
     */
    getFileExtension(filename) {
        return filename.split('.').pop() || '';
    }

    /**
     * Validar si el tipo MIME está permitido
     * @param {string} mimeType - Tipo MIME del archivo
     * @returns {boolean} True si está permitido
     */
    isMimeTypeAllowed(mimeType) {
        if (!mimeType) return false;
        
        return this.ALLOWED_MIME_TYPES.some(allowed => 
            mimeType.toLowerCase().includes(allowed.toLowerCase())
        );
    }

    /**
     * Validar nombre de archivo para detectar tipo de examen
     * @param {string} filename - Nombre del archivo
     * @returns {string} Tipo de examen detectado
     */
    detectExamType(filename) {
        const name = filename.toLowerCase();

        if (['radiografia', 'radiografía', 'rx', 'xray', 'x-ray'].some(k => name.includes(k))) {
            return 'xray';
        }
        if (['tomografia', 'tomografía', 'ct', 'tac', 'scan'].some(k => name.includes(k))) {
            return 'ct';
        }
        if (['resonancia', 'mri', 'rmn'].some(k => name.includes(k))) {
            return 'mri';
        }
        if (['ecografia', 'ecografía', 'ultrasound', 'eco'].some(k => name.includes(k))) {
            return 'ultrasound';
        }
        if (['mamografia', 'mamografía'].some(k => name.includes(k))) {
            return 'mammography';
        }
        if (['dicom', 'dcm'].some(k => name.includes(k))) {
            return 'dicom';
        }

        return 'lab';
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.MedicalFileValidator = MedicalFileValidator;
}