<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\PercentageChangeRequest;
use App\Http\Requests\Api\PercentageChangeBetweenDatesRequest;
use Illuminate\Support\Facades\Http;

class CurrencyController extends Controller
{
    public function percentageChangeInPeriod(PercentageChangeRequest $request)
    {
        $data = $request->all();

        $toDate = null;
        $fromDate = $data['date'];
        $base = $data['base'];
        $period = $data['period'];
        
        $fromDateTime = strtotime($fromDate);

        switch ($period) {
            case 'y':
                $year = date("Y", strtotime("$fromDate -1 year"));
                $toDate = $year . '-' . date('m-d', $fromDateTime);
                break;
            case 'm':
                $month = date("m", strtotime("$fromDate -1 month"));
                $toDate = date('Y', $fromDateTime) . '-'. $month . '-' . date('d', $fromDateTime);
                break;
            case 'w':
                $date = date("d", strtotime("$fromDate -1 week"));
                $toDate = date('Y-m', $fromDateTime) . '-'. $date;
                break;
            case 'd':
                $date = date("d", strtotime("$fromDate -1 day"));
                $toDate = date('Y-m', $fromDateTime) . '-'. $date;
                break;
            default:
                $year = date("Y", strtotime("$fromDate -1 year"));
                $toDate = $year . '-' . date('m-d', $fromDateTime);
                break;
        }

        $responseFromDate = Http::get("https://api.vatcomply.com/rates?date=${fromDate}&base={$base}");
        $responseToDate = Http::get("https://api.vatcomply.com/rates?date=${toDate}&base={$base}");

        if($responseFromDate->successful() && $responseToDate->successful()) {

            $responseFromDate = (array) json_decode($responseFromDate->body());
            $responseFromDate = (array) $responseFromDate['rates'];

            $responseToDate = (array) json_decode($responseToDate->body());
            $responseToDate = (array) $responseToDate['rates'];

            $percentageChange = $this->calculatePercentageChange($responseFromDate, $responseToDate);
            
            return response()->json([
                'date' => $fromDate,
                'base' => $base,
                'rates' => $percentageChange
            ]);
        } else {

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while fetching rates'
            ]);
        }
    }

    public function percentageChange(PercentageChangeBetweenDatesRequest $request)
    {
        $data = $request->all();

        $base = $data['base'];
        $startDate = date('Y-m-d', strtotime($data['start_date']));
        $endDate = date('Y-m-d', strtotime($data['end_date']));
        
        $startDateResponse = Http::get("https://api.vatcomply.com/rates?date=${startDate}&base={$base}");
        $endDateResponse = Http::get("https://api.vatcomply.com/rates?date=${endDate}&base={$base}");

        if($startDateResponse->successful() && $endDateResponse->successful()) {

            $startDateResponse = (array) json_decode($startDateResponse->body());
            $startDateResponse = (array) $startDateResponse['rates'];

            $endDateResponse = (array) json_decode($endDateResponse->body());
            $endDateResponse = (array) $endDateResponse['rates'];

            $percentageChange = $this->calculatePercentageChange($endDateResponse, $startDateResponse);
            
            return response()->json([
                'base' => $base,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rates' => $percentageChange
            ]);
        } else {

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while fetching rates'
            ]);
        }
    }

    private function calculatePercentageChange($responseFromDate, $responseToDate)
    {
        $finalArray = [];

        foreach($responseFromDate as $key1 => $resFromVal) {

            foreach($responseToDate as $key2 => $resToVal) {
                
                if($key1 == $key2) {

                    if($resFromVal > $resToVal) {

                        $change = (
                            ($resFromVal - $resToVal) / ($resFromVal + $resToVal) * 100
                        );
                        $change = '+ ' . $change . ' %';
                    } elseif($resToVal > $resFromVal) {

                        $change = (
                            ($resToVal - $resFromVal) / ($resToVal + $resFromVal) * 100
                        );
                        $change = '- ' . $change . ' %';
                    } else {
                        $change = '0 %';
                    }

                    $finalArray[$key1] = $change;
                }
            }
        }
        return $finalArray;
    }
}
