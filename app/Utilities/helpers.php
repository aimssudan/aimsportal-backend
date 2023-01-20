<?php

use App\Http\Resources\CodelistTranslationResource;
use App\Models\Codelist;
use App\Models\CodelistOption;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


if (!function_exists('iati_get_code_options'))
{
    function iati_get_code_options(string $codeName, string $language='en') : mixed
    {
        $codelist = Codelist::where('name', $codeName)->first();
        if ($codelist) {
            if ($language != 'en') {
                return transform_translation($codelist->translations->where('lang', $language));
            }
            return $codelist->options;
        }
        return collect([]);
    }
}

if (!function_exists('iati_get_code_value'))
{
    function iati_get_code_value(?string $codeName, ?string $codeValue, $language='en') : mixed
    {
        if (null === $codeName || $codeValue === null) return null;
        return iati_get_code_options($codeName, $language)->where('code', $codeValue)->first(); 
    }
}

function transform_translation(Collection $translations)
{
   $modified = $translations->map(function ($item) {
        return [
            'id' => $item->id,
            'code' => $item->codelist_option->code,
            'name' => $item->name,
            'description' => $item->description
        ];
        
    });

    return $modified;
}




