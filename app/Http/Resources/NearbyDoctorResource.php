<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NearbyDoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isClinicStaff = ! is_null($this->clinic_id);
        $address = $isClinicStaff ? $this->clinic?->address : $this->address;
        $clinicName = $isClinicStaff ? $this->clinic?->name : null;

        return [
            'id'              => $this->id,
            'name'            => $clinicName ? "{$this->name} ({$clinicName})" : $this->name,
            'slug'            => $this->slug,
            'latitude'        => $address?->latitude,
            'longitude'       => $address?->longitude,
            'distance'        => isset($this->distance) ? round((float) $this->distance, 1) : null,
            'nextAppointment' => $this->next_available_slot ?? 'Mañana, 10:00 AM',
            'profileUrl'      => route('partner.public.profile', $this->slug),
        ];
    }
}
