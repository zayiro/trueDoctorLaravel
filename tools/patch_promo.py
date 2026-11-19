#!/usr/bin/env python3
"""Parcha routes/web.php, los dos sidebars y el listado admin de códigos. Idempotente. Uso: python3 patch_promo.py /var/www/html"""
import re, shutil, sys, os

root = sys.argv[1] if len(sys.argv) > 1 else '.'

def read(p):
    with open(os.path.join(root, p), newline='') as f:
        return f.read()

def write(p, s):
    full = os.path.join(root, p)
    if not os.path.exists(full + '.bak-promo'):
        shutil.copy(full, full + '.bak-promo')
    with open(full, 'w', newline='') as f:
        f.write(s)

# ---- 1. Rutas
p = 'routes/web.php'
s = read(p)
if 'partner.promo-codes.index' in s:
    print('rutas: ya estaban')
else:
    nl = '\r\n' if '\r\n' in s else '\n'
    block = nl.join([
        '',
        '',
        '// Códigos promocionales de doctores particulares y clínicas (el descuento lo asume quien lo crea).',
        "Route::middleware(['auth', 'role:doctor|clinic'])->group(function () {",
        "    Route::resource('partner/promo-codes', \\App\\Http\\Controllers\\PartnerPromoCodeController::class)",
        "        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])",
        "        ->names([",
        "            'index'   => 'partner.promo-codes.index',",
        "            'create'  => 'partner.promo-codes.create',",
        "            'store'   => 'partner.promo-codes.store',",
        "            'edit'    => 'partner.promo-codes.edit',",
        "            'update'  => 'partner.promo-codes.update',",
        "            'destroy' => 'partner.promo-codes.destroy',",
        "        ]);",
        "});",
        '',
    ])
    write(p, s.rstrip() + block)
    print('rutas: agregadas al final de web.php')

# ---- 2. Sidebars
ICON = ('<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">'
        '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>')

def patch_sidebar(path, anchor_name, with_visible):
    s = read(path)
    if 'partner.promo-codes.index' in s:
        print(path, ': ya estaba')
        return
    nl = '\r\n' if '\r\n' in s else '\n'
    i = s.find("'name' => '" + anchor_name + "'")
    if i < 0:
        print(path, ': NO encontré el ancla', anchor_name, '(agrega el item a mano)')
        return
    # inicio del bloque "[" que contiene el ancla
    j = s.rfind('[', 0, i)
    line_start = s.rfind('\n', 0, j) + 1
    indent = s[line_start:j]
    item = [
        indent + '[',
        indent + "    'name' => 'Códigos promocionales',",
        indent + "    'icon' => '" + ICON + "',",
        indent + "    'href' => route('partner.promo-codes.index'),",
        indent + "    'active' => request()->routeIs('partner.promo-codes.*'),",
    ]
    if with_visible:
        item.append(indent + "    'visible' => $hasIndividualPlan,")
    item.append(indent + '],')
    s = s[:line_start] + nl.join(item) + nl + s[line_start:]
    write(path, s)
    print(path, ': item agregado')

patch_sidebar('resources/views/layouts/includes/admin/sidebar.blade.php', 'Conocimientos Médicos', True)
patch_sidebar('resources/views/layouts/includes/clinic/sidebar.blade.php', 'Horarios de Atención', False)

# ---- 3. Listado admin: mostrar de quién es cada código
p = 'resources/views/administrator/promo-codes/index.blade.php'
s = read(p)
old = '<td class="p-4 font-mono font-bold text-indigo-600">{{ $code->code }}</td>'
new = ('<td class="p-4 font-mono font-bold text-indigo-600">{{ $code->code }}'
       '<span class="block font-sans text-xs font-normal text-gray-400">{{ $code->ownerLabel() }}</span></td>')
if ('ownerLabel' in s):
    print('admin index: ya estaba')
elif old in s:
    write(p, s.replace(old, new))
    print('admin index: parchado')
else:
    print('admin index: NO encontré la celda del código (opcional)')
