<?php

namespace App\Http\Controllers;
use App\Models\UploadGateEntryBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UploadgateentrybillController extends Controller {

    public function list() {
        return view('uploadgateentrybill.list');
    }

    public function form($id = null) {
        $invoice = null;
        $nextInvoiceNumber = $this->getNextInvoiceNumber();
        if ($id) {
            $invoice = UploadGateEntryBill::find($id);
            if (!$invoice) return redirect()->route('uploadgateentrybill.list');
        }
        return view('uploadgateentrybill.form', [
            'invoice' => $invoice,
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'title' => $id ? 'Edit Invoice' : 'Add Invoice'
        ]);
    }

    private function getNextInvoiceNumber() {
        $lastInvoice = UploadGateEntryBill::orderBy('Id', 'DESC')->first();
        $nextNumber = $lastInvoice ? $lastInvoice->Id + 1 : 1;
        return 'INV-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function datatable(Request $request)
    {
        try {
            $search = $request->input('search.value', '');
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $draw = (int) $request->input('draw', 1);

            // Join with Online_Tbl_Users table
            $query = UploadGateEntryBill::query()
                ->leftJoin('Online_Tbl_Users', 'VendorInvoices.ApprovedByAdminID', '=', 'Online_Tbl_Users.ID');

            if (!empty($search)) {
                $query->whereRaw("LOWER([InvoiceNumber]) LIKE ?", ['%' . strtolower($search) . '%'])
                    ->orWhereRaw("LOWER([ApprovalStatus]) LIKE ?", ['%' . strtolower($search) . '%']);
            }

            $recordsTotal = UploadGateEntryBill::count();
            $recordsFiltered = $query->count();
            
            $results = $query->select(
                'VendorInvoices.*',
                DB::raw("Online_Tbl_Users.FirstName + ' ' + Online_Tbl_Users.LastName as AdminName")
            )
            ->skip($start)
            ->take($length)
            ->get();

            $data = $results->map(function ($item) {

                return [
                    'Id' => $item->Id,
                    'InvoiceNumber' => $item->InvoiceNumber ?? '-',
                    'ImagePath' => $item->ImagePath
                        ? asset($item->ImagePath)
                        : null,
                    'ApprovalStatus' => $item->ApprovalStatus ?? 'Pending',
                    'ApprovedByAdminID' => $item->ApprovedByAdminID ?? '-',
                    'ApprovedByAdminName' => $item->AdminName ?? '-',
                    'CreatedDate' => $item->CreatedDate 
                        ? \Carbon\Carbon::parse($item->CreatedDate)->format('d-m-Y')
                        : '-',
                    'ApprovalNotes' => $item->ApprovalNotes ?? '-',
                ];

            })->toArray();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Datatable Error: ' . $e->getMessage());
            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {

            $request->validate([
                'image'=>'image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            // Check if we're editing or creating
            if ($request->has('Id') && $request->Id) {
                // EDIT MODE - Update existing invoice
                $invoice = UploadGateEntryBill::find($request->Id);
                
                if (!$invoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice not found'
                    ], 404);
                }
                
                // Update image only if new image is uploaded
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $path = 'invoices/'.date('Y/m');

                    if (!is_dir(public_path($path))) {
                        mkdir(public_path($path), 0755, true);
                    }

                    // Delete old image if exists
                    if ($invoice->ImagePath && file_exists(public_path($invoice->ImagePath))) {
                        unlink(public_path($invoice->ImagePath));
                    }

                    $filename = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path($path), $filename);
                    $invoice->ImagePath = $path.'/'.$filename;
                }
                
                $invoice->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice '.$invoice->InvoiceNumber.' updated successfully',
                    'invoice' => $invoice
                ]);
                
            } else {
                // CREATE MODE - New invoice
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                ]);

                $invoice = new UploadGateEntryBill();
                $lastInvoice = UploadGateEntryBill::orderBy('Id','DESC')->first();
                $nextNumber = $lastInvoice ? $lastInvoice->Id + 1 : 1;
                $invoice->InvoiceNumber = 'INV-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                $invoice->ApprovalStatus = 'Pending';

                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $path = 'invoices/'.date('Y/m');

                    if (!is_dir(public_path($path))) {
                        mkdir(public_path($path), 0755, true);
                    }

                    $filename = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path($path), $filename);
                    $invoice->ImagePath = $path.'/'.$filename;
                }

                $invoice->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice '.$invoice->InvoiceNumber.' uploaded successfully',
                    'invoice' => $invoice
                ]);
            }

        } catch(\Exception $e) {

            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = UploadGateEntryBill::query()
                ->leftJoin('Online_Tbl_Users', 'VendorInvoices.ApprovedByAdminID', '=', 'Online_Tbl_Users.ID')
                ->where('VendorInvoices.Id', $id)
                ->select(
                    'VendorInvoices.*',
                    DB::raw("Online_Tbl_Users.FirstName + ' ' + Online_Tbl_Users.LastName as AdminName")
                )
                ->first();
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'Id' => $invoice->Id,
                    'InvoiceNumber' => $invoice->InvoiceNumber,
                    'ImagePath' => $invoice->ImagePath ? asset($invoice->ImagePath) : null,
                    'ApprovalStatus' => $invoice->ApprovalStatus,
                    'ApprovedByAdminID' => $invoice->ApprovedByAdminID,
                    'ApprovedByAdminName' => $invoice->AdminName ?? '-',
                    'ApprovedAt' => $invoice->ApprovedAt ? \Carbon\Carbon::parse($invoice->ApprovedAt)->format('d-m-Y') : null,
                    'CreatedDate' => $invoice->CreatedDate ? \Carbon\Carbon::parse($invoice->CreatedDate)->format('d-m-Y') : null,
                    'ApprovalNotes' => $invoice->ApprovalNotes
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Show Invoice Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching invoice details'
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $invoice = UploadGateEntryBill::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $invoice->ApprovalStatus = 'Approved';
            $invoice->ApprovedByAdminID = auth()->id();
            $invoice->ApprovedAt = now();
            $invoice->ApprovalNotes = $request->input('notes', '');
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice approved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Approve Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error approving invoice'
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $invoice = UploadGateEntryBill::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $invoice->ApprovalStatus = 'Rejected';
            $invoice->ApprovedByAdminID = auth()->id();
            $invoice->ApprovedAt = now();
            $invoice->ApprovalNotes = $request->input('notes', '');
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice rejected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Reject Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting invoice'
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $invoice = UploadGateEntryBill::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            // Delete image if exists
            if ($invoice->ImagePath && file_exists(public_path($invoice->ImagePath))) {
                unlink(public_path($invoice->ImagePath));
            }

            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting invoice'
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Please login'
                ], 401);
            }

            // Check if user is admin (user_role = 1)
            $user = DB::table('Online_Tbl_Users')
                ->where('ID', auth()->id())
                ->first();

            if (!$user || $user->user_role != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only admin can update status'
                ], 403);
            }

            $invoice = UploadGateEntryBill::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $status = $request->input('status', 'Pending');

            if ($status === 'Pending') {
                $invoice->ApprovalStatus = 'Pending';
                $invoice->ApprovedByAdminID = null;
                $invoice->ApprovedAt = null;
                $invoice->ApprovalNotes = null;
            } else {
                $invoice->ApprovalStatus = $status;
                $invoice->ApprovedByAdminID = auth()->id();
                $invoice->ApprovedAt = now();
            }

            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Update Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status'
            ], 500);
        }
    }
}