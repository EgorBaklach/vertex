<?php namespace App\Http\Controllers;

use App\Models\Sora\Pictures;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mime\MimeTypes;

class UploadController extends Controller
{
    private array $pictures = [];

    private function picture(int $pos): string
    {
        if($pos < 0) throw ValidationException::withMessages(['image' => ['Array Data Pictures doesnt have picture.']]); return $this->pictures[$pos] ?? $this->picture(--$pos);
    }

    public function __invoke(Request $request)
    {
        ['pictures' => $this->pictures, 'number' => $number, 'title' => $title] = $request->validate(['pictures' => 'required|array', 'pictures.*' => 'required|string', 'number' => 'required|string|max:255', 'title' => 'required|string|max:255']);

        $update = ['position' => 1, 'date_generate' => date('Y-m-d H:i:s'), 'uid' => $request->user()->id, 'title' => $title, 'selectGen' => null, 'svg' => null]; $counter = 4;

        $callback = fn($pos, $mime) => $number.'_'.++$pos.'.'.MimeTypes::getDefault()->getExtensions($mime)[0];

        $public = Storage::disk('public'); if($public->exists('sora/'.$number) && $public->deleteDirectory('sora/'.$number)) $update['position'] = (Pictures::max('position') ?? 1) + 1;

        while($counter--)
        {
            file_put_contents($path = stream_get_meta_data(tmpfile())['uri'], base64_decode($this->picture($counter))); $mime = mime_content_type($path);

            if($mime !== 'image/webp') throw ValidationException::withMessages(['image' => ['The provided file is not a valid image.']]);

            $public->putFileAs('sora/'.$number, new UploadedFile(path: $path, originalName: $name = $callback($counter, $mime), mimeType: $mime), $name);
        }

        Pictures::query()->where('number', $number)->update($update); return response()->json(['status' => 200, 'reason_phrase' => 'Ok']);
    }
}
