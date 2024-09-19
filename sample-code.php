namespace App\Http\Controllers;

use App\Jobs\ProcessOrder;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Handle bulk processing of orders.
     */
    public function processOrders(Request $request)
    {
        $orderIds = $request->input('order_ids');

        // Validate input
        if (!is_array($orderIds) || empty($orderIds)) {
            return response()->json(['error' => 'Invalid order IDs provided'], 400);
        }

        try {
            DB::beginTransaction();
            
            $orders = Order::whereIn('id', $orderIds)->lockForUpdate()->get();
            
            if ($orders->isEmpty()) {
                return response()->json(['error' => 'No orders found for processing'], 404);
            }

            foreach ($orders as $order) {
                // Dispatch each order for processing in the background
                ProcessOrder::dispatch($order);
            }

            DB::commit();
            return response()->json(['message' => 'Orders are being processed'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process orders: ' . $e->getMessage()], 500);
        }
    }
}
