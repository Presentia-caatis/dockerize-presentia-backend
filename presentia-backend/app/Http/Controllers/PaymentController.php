<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Payment;
class PaymentController extends Controller
{
    public function index()
    {

        $data = Payment::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Payments retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'amount' => 'required|integer',
            'status' => 'required|string',
        ]);


        $data = Payment::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Payment created successfully',
            'data' => $data
        ],201);

    }

    public function getById(Payment $payment)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Payment retrieved successfully',
            'data' => $payment
        ]);

    }

    public function update(Request $request, Payment $payment)
    {
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'amount' => 'required|integer',
            'status' => 'required|string',
        ]);

        $payment->update($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Payment updated successfully',
            'data' => $payment
        ]);

    }

    public function destroy(Payment $payment)
    {

        $payment->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Payment deleted successfully'
        ]);

    }
}
