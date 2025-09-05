<?

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'from_vendor_id',
        'to_vendor_id',
        'quantity',
        'status',   // pending, approved, completed
        'initiated_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function fromVendor()
    {
        return $this->belongsTo(Vendor::class, 'from_vendor_id');
    }

    public function toVendor()
    {
        return $this->belongsTo(Vendor::class, 'to_vendor_id');
    }
}
