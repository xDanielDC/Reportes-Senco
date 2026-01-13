<?php

namespace App\Http\Controllers;

use App\Mail\SystemMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SupplierDeliveryController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $query = DB::connection('ssf')
            ->table('V_OC')
            ->orderBy('DELIVERY_DATE', 'DESC')
            ->get();

        $orders = array_map(function ($row) {
            return [
                "name" => $row[0]->PROVIDER_NAME,
                "document" => $row[0]->PROVIDER_CODE,
                "oc" => $row[0]->OC,
                "registries" => $row
            ];
        }, $query->groupBy('OC')->values()->toArray());

        return Inertia::render('SupplierDelivery/Index', [
            'orders' => $orders
        ]);
    }

    public function update(Request $request)
    {
        #TODO: PENDIENTE SP
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMail(Request $request)
    {
        try {
            $query = DB::connection('ssf')
                ->table('V_OC')
                ->orderBy('DELIVERY_DATE', 'DESC')
                ->where('PROVIDER_CODE', '=', $request->document)
                ->where('OC', '=', $request->oc)
                ->first();

            $url = $this->urlGenerator($query->PROVIDER_CODE);
            $email_array = array_values(array_filter(array_map('trim', explode(';', $query->PROVIDER_MAIL))));

            Mail::to(['daniel.hincapie972423@gmail.com'])
                ->send(new SystemMail("Entrega de Mercancía",
                    "La orden de compra $query->OC esta pendiente por confirmar fecha y cantidad a entregar, por favor ingrese al siguiente link y complete la información",
                    $url
                ));

            return response()->json('success', 200);
        }catch (\Exception $ex){
            return response()->json($ex->getMessage(), 500);
        }

    }

    /**
     * @param $hash
     * @return Response
     */
    public function show($hash)
    {
        $provider = Crypt::decryptString($hash);

        $query = DB::connection('ssf')
            ->table('V_OC')
            ->where('PROVIDER_CODE', '=', $provider)
            ->orderBy('DELIVERY_DATE', 'DESC')
            ->get();

        $registry = (object)[
            "supplier" => (object)[
                "name" => $query->first()->PROVIDER_NAME,
                "document" => $query->first()->PROVIDER_CODE,
            ],
            "orders" => array_map(function ($row) {
                return [
                    'oc' => $row[0]->OC,
                    'delivery_date' => $row[0]->DELIVERY_DATE,
                    "registries" => $row
                ];
            }, $query->groupBy('OC')->values()->toArray())
        ];

        return Inertia::render('SupplierDelivery/Show', [
            'registry' => $registry
        ]);
    }


    /**
     * @param $provider
     * @return string
     */
    protected function urlGenerator($provider)
    {
        $hash = Crypt::encryptString($provider);
        return route('supplier-delivery.show', $hash);
    }
}
