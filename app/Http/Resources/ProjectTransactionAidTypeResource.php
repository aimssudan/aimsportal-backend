<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTransactionAidTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'iati_code' => $this->code,
            'iati_vocabulary' => $this->vocabulary,
            'code' => $this->iati_code()->name ?? null,
            'vocabulary' => $this->iati_vocabulary()->name ?? null,
            'audits' => (can_see_audits($this->resource)) ? AuditResource::collection($this->whenLoaded('audits')) : '',
        ];
    }
}
