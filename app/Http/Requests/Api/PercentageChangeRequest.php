<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class PercentageChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $response = Http::get('https://api.vatcomply.com/currencies');

        $currencies = [];
        $periods = ['y', 'm', 'w', 'd'];

        if($response->successful()) {
            $currencies = array_keys(
                (array) json_decode($response->body())
            );
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while fetching currencies'
            ]);
        }

        return [
            'base' => ['required', Rule::in($currencies)],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'period' => ['required', Rule::in($periods)]
        ];
    }
}
