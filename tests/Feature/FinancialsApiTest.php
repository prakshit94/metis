<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Refund;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Customers\Models\Party;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Party $customer;
    protected Order $order;
    protected Invoice $invoice;
    protected Payment $payment;
    protected OrderReturn $orderReturn;
    protected Refund $refund;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->user->assignRole('Super Admin');
        $this->customer = Party::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'type' => 'customer',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
        ]);

        $this->order = Order::create([
            'order_no' => 'ORD-TEST-99',
            'type' => 'sale',
            'party_id' => $this->customer->id,
            'order_date' => now(),
            'status' => 'confirmed',
            'net_amount' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no' => 'INV-TEST-99',
            'order_id' => $this->order->id,
            'invoice_date' => now(),
            'total_amount' => 1000.00,
            'tax_amount' => 0.00,
            'net_amount' => 1000.00,
            'status' => 'unpaid',
        ]);

        $this->payment = Payment::create([
            'payment_no' => 'PAY-TEST-99',
            'invoice_id' => $this->invoice->id,
            'order_id' => $this->order->id,
            'amount' => 500.00,
            'payment_method' => 'credit_card',
            'transaction_id' => 'TXN-TEST-99',
            'payment_date' => now(),
            'status' => 'authorized',
        ]);

        $this->orderReturn = OrderReturn::create([
            'order_id' => $this->order->id,
            'return_no' => 'RET-TEST-99',
            'status' => 'pending',
            'financial_status' => 'pending',
        ]);

        $this->refund = Refund::create([
            'refund_no' => 'REF-TEST-99',
            'order_id' => $this->order->id,
            'invoice_id' => $this->invoice->id,
            'order_return_id' => $this->orderReturn->id,
            'amount' => 500.00,
            'payment_method' => 'credit_card',
            'transaction_id' => 'REF-TXN-99',
            'status' => 'pending',
        ]);
    }

    public function test_can_fetch_invoices_list_with_search_and_stats()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/invoices?search=INV-TEST-99');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoices' => ['data'],
                'stats' => ['total_invoiced', 'collected_amount', 'pending_amount', 'avg_value'],
            ]);

        $this->assertEquals('INV-TEST-99', $response->json('invoices.data.0.invoice_no'));
    }

    public function test_can_bulk_update_invoice_status()
    {
        // Controller prevents manually setting to paid/unpaid
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices/bulk-status', [
                'ids' => [$this->invoice->id],
                'status' => 'paid',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        // Cancelling an unpaid invoice should work
        $response2 = $this->actingAs($this->user)
            ->postJson('/api/invoices/bulk-status', [
                'ids' => [$this->invoice->id],
                'status' => 'cancelled',
            ]);

        $response2->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('cancelled', $this->invoice->refresh()->status);
    }

    public function test_can_fetch_payments_list_with_search_and_stats()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/payments?search=PAY-TEST-99');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payments' => ['data'],
                'stats' => ['total_volume', 'completed_amount', 'authorized_amount', 'failed_amount'],
            ]);

        $this->assertEquals('PAY-TEST-99', $response->json('payments.data.0.payment_no'));
    }

    public function test_can_bulk_update_payment_status_and_sync_invoice()
    {
        // Initially, the payment is authorized (not captured).
        // Let's capture the payment. This should update the invoice paid amount and status.
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/bulk-status', [
                'ids' => [$this->payment->id],
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('completed', $this->payment->refresh()->status);
        
        // Check that invoice status is synced. Invoice has total 1000.00. Payment is 500.00.
        // Paid 500 out of 1000 means invoice status should be partially_paid.
        $this->assertEquals('partially_paid', $this->invoice->refresh()->status);
        $this->assertEquals(500.00, $this->invoice->paid_amount);
    }

    public function test_can_fetch_refunds_list_with_search_and_stats()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/refunds?search=REF-TEST-99');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'refunds' => ['data'],
                'stats' => ['total_refunded', 'pending', 'failed', 'processed_today'],
            ]);

        // Status completed gets mapped to processed for UI compatibility
        $this->assertEquals('REF-TEST-99', $response->json('refunds.data.0.refund_no'));
    }

    public function test_can_bulk_update_refund_status()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/refunds/bulk-status', [
                'ids' => [$this->refund->id],
                'status' => 'processed', // processed maps to completed in DB
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('completed', $this->refund->refresh()->status);
    }
}
