<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PercentageChangeBetweenDatesRequest extends FormRequest
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
        $data = request()->all();
        
        if($data['start_date'] > $data['end_date']) {
            throw ValidationException::withMessages([
                'start_date' => 'Start date must be smaller then End date.'
            ]);
        }

        $response = Http::get('https://api.vatcomply.com/currencies');

        $currencies = [];

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
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d']
        ];
    }
}
