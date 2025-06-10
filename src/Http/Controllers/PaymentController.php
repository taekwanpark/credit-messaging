<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class PaymentController extends Controller
{
    public function show(Request $request, SiteCredit $siteCredit)
    {
        return view('crm::payment', [
            'siteCredit' => $siteCredit,
        ]);
    }
}
