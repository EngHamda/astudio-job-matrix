<?php

namespace App\Http\Resources;

use App\Models\CoreJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $languages
 * @property mixed $locations
 * @property mixed $categories
 */
class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        // Cast the resource to CoreJob to get better IDE support
        /** @var CoreJob $this */

        // Get all dynamic attributes for this job
        $attributes = $this->jobAttributeValues()
            ->with('attribute')
            ->get()
            ->mapWithKeys(function ($attributeValue) {
                $name = $attributeValue->attribute->name;
                return [$name => $attributeValue->casted_value];
            });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'company_name' => $this->company_name,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'is_remote' => $this->is_remote,
            'job_type' => $this->job_type,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include relationships
            'languages' => $this->whenLoaded('languages', function () {
                return $this->languages->map(function ($language) {
                    return [
                        'id' => $language->id,
                        'name' => $language->name
                    ];
                });
            }),

            'locations' => $this->whenLoaded('locations', function () {
                return $this->locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'city' => $location->city,
                        'state' => $location->state,
                        'country' => $location->country,
//                        'full_location' => $location->full_location
                    ];
                });
            }),

            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name
                    ];
                });
            }),

            // Include dynamic attributes
            'attributes' => $attributes,
        ];
    }
}
