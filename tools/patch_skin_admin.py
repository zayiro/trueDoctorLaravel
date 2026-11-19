#!/usr/bin/env python3
"""Instala la vista admin de análisis de piel y programa las purgas. Idempotente.
Uso: python3 patch_skin_admin.py /var/www/html
 - routes/web.php: rutas administrator.skin-analyses.* (al final, grupo auth + role:admin)
 - sidebar del admin: ítem 'Análisis de piel' antes de 'SEO síntomas'
 - routes/console.php: programa medical:purge-stale y skin:purge-stale (diario, de madrugada)"""
import os, shutil, sys

root = sys.argv[1] if len(sys.argv) > 1 else '.'

def read(p):
    with open(os.path.join(root, p), newline='') as f:
        return f.read()

def write(p, s):
    full = os.path.join(root, p)
    if not os.path.exists(full + '.bak-skin'):
        shutil.copy(full, full + '.bak-skin')
    with open(full, 'w', newline='') as f:
        f.write(s)

# ---- 1. Rutas
p = 'routes/web.php'
s = read(p)
if 'administrator.skin-analyses.index' in s:
    print('rutas: ya estaban')
else:
    nl = '\r\n' if '\r\n' in s else '\n'
    c = '\\App\\Http\\Controllers\\Admin\\SkinAnalysisAdminController::class'
    block = nl.join([
        '', '',
        '// Análisis de piel en el panel del administrador (reenviar informe y eliminar).',
        "Route::middleware(['auth', 'role:admin'])->group(function () {",
        f"    Route::get('/admin/skin-analyses', [{c}, 'index'])->name('administrator.skin-analyses.index');",
        f"    Route::post('/admin/skin-analyses/{{skinAnalysis}}/resend', [{c}, 'resend'])->name('administrator.skin-analyses.resend');",
        f"    Route::delete('/admin/skin-analyses/{{skinAnalysis}}', [{c}, 'destroy'])->name('administrator.skin-analyses.destroy');",
        '});', '',
    ])
    write(p, s.rstrip() + block)
    print('rutas: agregadas')

# ---- 2. Sidebar del admin
p = 'resources/views/layouts/includes/administrator/sidebar.blade.php'
s = read(p)
if 'administrator.skin-analyses.index' in s:
    print('sidebar: ya estaba')
else:
    nl = '\r\n' if '\r\n' in s else '\n'
    i = s.find("'name' => 'SEO síntomas'")
    if i < 0:
        print('sidebar: NO encontré el ancla "SEO síntomas" (agrega el ítem a mano)')
    else:
        j = s.rfind('[', 0, i)
        line_start = s.rfind('\n', 0, j) + 1
        ind = s[line_start:j]
        icon = ('<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">'
                '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>')
        item = nl.join([
            ind + '[',
            ind + "    'name' => 'Análisis de piel',",
            ind + "    'icon' => '" + icon + "',",
            ind + "    'href' => route('administrator.skin-analyses.index'),",
            ind + "    'active' => request()->routeIs('administrator.skin-analyses.*'),",
            ind + '],',
        ])
        write(p, s[:line_start] + item + nl + s[line_start:])
        print('sidebar: ítem agregado')

# ---- 3. Programación de las purgas
p = 'routes/console.php'
s = read(p)
if 'skin:purge-stale' in s:
    print('console: ya estaba')
else:
    nl = '\r\n' if '\r\n' in s else '\n'
    block = nl.join([
        '', '',
        '// Datos sensibles: borra subidas abandonadas y archivos de órdenes sin pagar o ya analizadas.',
        "Schedule::command('medical:purge-stale')->timezone('America/Bogota')->dailyAt('03:30')->withoutOverlapping()->onOneServer();",
        "Schedule::command('skin:purge-stale')->timezone('America/Bogota')->dailyAt('03:40')->withoutOverlapping()->onOneServer();",
        '',
    ])
    write(p, s.rstrip() + block)
    print('console: purgas programadas')
