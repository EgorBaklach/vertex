<?php namespace App\Http\Controllers;

use App\Models\Sora\Pictures;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;

class UploadController extends Controller
{
    private array $pictures = [];

    private function picture($pos): string
    {
        return $this->pictures[$pos] ?? $this->picture(--$pos);
    }

    public function __invoke(Request $request)
    {
        ['pictures' => $this->pictures, 'number' => $number] = $request->validate(['pictures' => 'required|array', 'pictures.*' => 'required|string', 'number' => 'required|string|max:255']);

        $counter = 4; $update = ['status' => 1, 'selectGen' => null, 'svg' => null]; $callback = fn($pos, $mime) => $number.'_'.++$pos.'.'.MimeTypes::getDefault()->getExtensions($mime)[0];

        $public = Storage::disk('public'); if($public->exists('sora/'.$number) && $public->deleteDirectory('sora/'.$number)) $update['status'] = 2;

        while($counter--)
        {
            file_put_contents($path = stream_get_meta_data(tmpfile())['uri'], base64_decode($this->picture($counter))); $mime = mime_content_type($path);

            $public->putFileAs('sora/'.$number, new UploadedFile(path: $path, originalName: $name = $callback($counter, $mime), mimeType: $mime), $name);
        }

        Pictures::query()->where('number', $number)->update($update); return response()->json(['status' => 200, 'reason_phrase' => 'Ok']);
    }
}
