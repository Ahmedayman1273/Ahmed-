<?PHP
namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;

class FrameOptionsHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY'); 
        return $response;
    }
}
