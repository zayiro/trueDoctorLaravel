<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Vista de gestión de galería.
     */
    public function index()
    {
        $owner  = $this->getOwner();
        $images = $owner->gallery()->get();

        return view('partner.gallery.index', compact('images', 'owner'));
    }

    /**
     * Subir imagen.
     */
    public function store(Request $request)
    {
        $request->validate([
            'images.*'   => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'images'   => 'required|array|max:10',
        ]);

        $owner      = $this->getOwner();
        $lastOrder  = $owner->gallery()->max('order') ?? 0;

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('gallery/' . class_basename($owner) . '/' . $owner->id, 'public');

            GalleryImage::create([
                'galleryable_id'   => $owner->id,
                'galleryable_type' => get_class($owner),
                'path'             => $path,
                'caption'          => null,
                'order'            => $lastOrder + $index + 1,
            ]);
        }

        return back()->with('success', 'Imágenes subidas correctamente.');
    }

    /**
     * Actualizar caption.
     */
    public function update(Request $request, GalleryImage $image)
    {
        $this->authorizeImage($image);

        $request->validate([
            'caption' => 'nullable|string|max:150',
        ]);

        $image->update(['caption' => $request->caption]);

        return response()->json(['ok' => true]);
    }

    /**
     * Reordenar imágenes.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:gallery_images,id',
        ]);

        foreach ($request->order as $position => $id) {
            GalleryImage::where('id', $id)->update(['order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Eliminar imagen.
     */
    public function destroy(GalleryImage $image)
    {
        $this->authorizeImage($image);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Imagen eliminada.');
    }

    /**
     * Obtener el perfil del usuario autenticado (doctor o clínica).
     */
    private function getOwner()
    {
        $user = Auth::user();

        return match ($user->role) {
            'doctor' => $user->doctor,
            'clinic' => $user->clinic,
            default  => abort(403),
        };
    }

    /**
     * Verificar que la imagen pertenece al usuario autenticado.
     */
    private function authorizeImage(GalleryImage $image): void
    {
        $owner = $this->getOwner();

        if (
            $image->galleryable_id !== $owner->id ||
            $image->galleryable_type !== get_class($owner)
        ) {
            abort(403, 'No tienes permiso para gestionar esta imagen.');
        }
    }
}