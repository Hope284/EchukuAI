<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Frontend\FrontendSectionsStatus;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class BlogController extends Controller
{
    // single post

    public function post($slug)
    {
        $post = Blog::where('slug', $slug)->firstOrFail();

        // Check post status
        if (isset($post->status) && ! $post->status && ! Auth::user()?->isAdmin()) {
            abort(404);
        }

        $previousPost = Blog::where('id', '<', $post->id)
            ->where('status', true)
            ->orderByDesc('id')
            ->first();

        $nextPost = Blog::where('id', '>', $post->id)
            ->where('status', true)
            ->orderBy('id')
            ->first();

        $relatedPosts = Blog::where('id', '!=', $post->id)
            ->where('status', true)
            ->where(function ($query) use ($post) {
                $categories = explode(',', $post->category);
                $tags = explode(',', $post->tag);
                foreach ($categories as $category) {
                    $query->orWhere('category', 'LIKE', '%' . $category . '%');
                }
                foreach ($tags as $tag) {
                    $query->orWhere('tag', 'LIKE', '%' . $tag . '%');
                }
            })
            ->orderByDesc('id')
            ->take(2)
            ->get();

        if ($post) {
            return view('blog.post', compact('post', 'previousPost', 'nextPost', 'relatedPosts'));
        }

        abort(404);
    }

    // archive pages

    public function index()
    {

        $fSecSettings = FrontendSectionsStatus::getCache();
        $posts_per_page = $fSecSettings->blog_a_posts_per_page;

        $posts = Blog::where('status', 1)->orderBy('id', 'desc')->paginate($posts_per_page);
        $hero = [
            'type'        => 'blog',
            'title'       => __('Agentic Era for DZEVA.'),
            'subtitle'    => __('DZEVA Updates'),
            'description' => __('With over 1,000 features already launched, Dzeva is evolving once again. Today, we step into the future with Agentic Era for Dzeva.'),
        ];

        return view('blog.index', compact('posts', 'hero'));
    }

    public function tags($slug)
    {

        $fSecSettings = FrontendSectionsStatus::first();
        $posts_per_page = $fSecSettings->blog_a_posts_per_page;

        $posts = Blog::where('tag', 'like', "%{$slug}%")->where('status', 1)->orderBy('id', 'desc')->paginate($posts_per_page);
        $hero = [
            'type'        => 'tag',
            'title'       => $slug,
            'subtitle'    => __('Tag Archive'),
            'description' => __($fSecSettings->blog_a_description),
        ];

        if ($posts->isEmpty()) {
            abort(404);
        }

        return view('blog.index', compact('posts', 'hero'));
    }

    public function categories($slug)
    {

        $fSecSettings = FrontendSectionsStatus::first();
        $posts_per_page = $fSecSettings->blog_a_posts_per_page;

        $posts = Blog::where('category', 'like', "%{$slug}%")->where('status', 1)->orderBy('id', 'desc')->paginate($posts_per_page);
        $hero = [
            'type'        => 'category',
            'title'       => $slug,
            'subtitle'    => __('Category Archive'),
            'description' => __($fSecSettings->blog_a_description),
        ];

        if ($posts->isEmpty()) {
            abort(404);
        }

        return view('blog.index', compact('posts', 'hero'));
    }

    public function author($user_id)
    {

        $fSecSettings = FrontendSectionsStatus::first();
        $posts_per_page = $fSecSettings->blog_a_posts_per_page;

        $posts = Blog::where('user_id', $user_id)->where('status', 1)->orderBy('id', 'desc')->paginate($posts_per_page);
        $hero = [
            'type'        => 'author',
            'title'       => $user_id,
            'subtitle'    => 'Author Archive',
            'description' => __($fSecSettings->blog_a_description),
        ];

        if ($posts->isEmpty()) {
            abort(404);
        }

        return view('blog.index', compact('posts', 'hero'));
    }

    // dashboard

    public function blogList()
    {
        $list = Blog::orderBy('id', 'desc')->get();

        return view('panel.blog.list', compact('list'));
    }

    public function blogAddOrUpdate($id = null)
    {
        if ($id == null) {
            $blog = null;
        } else {
            $blog = Blog::where('id', $id)->firstOrFail();
        }

        return view('panel.blog.form', compact('blog'));
    }

    public function blogDelete($id = null)
    {
        $post = Blog::where('id', $id)->firstOrFail();
        $featureImage = (string) $post->feature_image;
        $post->delete();

        if ($this->isOwnedBlogImage($featureImage)) {
            File::delete(public_path($featureImage));
        }

        return back()->with(['message' => __('Deleted Successfully'), 'type' => 'success']);
    }

    public function blogAddOrUpdateSave(Request $request)
    {
        $postId = $request->input('post_id');
        $isUpdate = filled($postId) && $postId !== 'undefined' && $postId !== 'null';

        $validator = Validator::make($request->all(), [
            'title'         => ['required', 'string', 'max:255'],
            'content'       => ['required', 'string'],
            'feature_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:10240'],
            'status'        => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $post = $isUpdate
            ? Blog::where('id', $postId)->firstOrFail()
            : new Blog;
        $oldFeatureImage = (string) $post->feature_image;
        $feature_image = null;

        if ($request->hasFile('feature_image')) {
            $path = 'upload/images/blog/';
            $absolutePath = public_path($path);
            $image = $request->file('feature_image');
            $baseSlug = Str::slug($request->input('slug') ?: $request->input('title'));
            $extension = Str::lower((string) $image->guessExtension());
            $image_name = bin2hex(random_bytes(10)) . '-' . $baseSlug . '.' . $extension;

            $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            if (! in_array($extension, $imageTypes, true)) {
                $data = [
                    'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                ];

                return response()->json($data, 422);
            }

            if ($extension === 'svg') {
                $sanitizer = new Sanitizer;
                $cleanSvg = $sanitizer->sanitize((string) file_get_contents($image->getRealPath()));
                if (! is_string($cleanSvg) || $cleanSvg === '') {
                    return response()->json(['errors' => [__('The SVG image could not be sanitized.')]], 422);
                }
                file_put_contents($image->getRealPath(), $cleanSvg);
            }

            try {
                File::ensureDirectoryExists($absolutePath, 0775, true);
                @chmod($absolutePath, 02775);
                $image->move($absolutePath, $image_name);
                @chmod($absolutePath . DIRECTORY_SEPARATOR . $image_name, 0664);
            } catch (Throwable $throwable) {
                Log::error('Blog feature image upload failed.', [
                    'user_id' => Auth::id(),
                    'error'   => $throwable->getMessage(),
                ]);

                return response()->json([
                    'errors' => [__('The feature image could not be stored. Please try a smaller image or contact support.')],
                ], 422);
            }

            $feature_image = $path . $image_name;
        }

        $post->title = $request->title;
        $post->content = $request->get('content');
        $post->feature_image = $feature_image ?? $post->feature_image;
        $post->slug = $this->uniqueBlogSlug($request->input('slug') ?: $request->input('title'), $isUpdate ? (int) $post->id : null);
        $post->seo_title = $request->seo_title;
        $post->seo_description = $request->seo_description;
        $post->category = $this->csvInput($request->input('category'));
        $post->tag = $this->csvInput($request->input('tag'));
        $post->status = (bool) $request->input('status', 1);
        $post->user_id = Auth::user()->id;
        try {
            $post->save();
        } catch (Throwable $throwable) {
            if ($feature_image) {
                File::delete(public_path($feature_image));
            }

            Log::error('Blog post save failed.', [
                'user_id' => Auth::id(),
                'post_id' => $postId,
                'error'   => $throwable->getMessage(),
            ]);

            return response()->json([
                'errors' => [__('The post could not be saved. Please review the form and try again.')],
            ], 500);
        }

        if ($feature_image && $oldFeatureImage !== $feature_image && $this->isOwnedBlogImage($oldFeatureImage)) {
            File::delete(public_path($oldFeatureImage));
        }

        return response()->json([
            'message'  => __('Post saved successfully.'),
            'redirect' => route('dashboard.admin.blog.addOrUpdate', $post->id),
            'preview'  => $post->status ? route('blog.post', $post->slug) : null,
        ]);
    }

    private function uniqueBlogSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Blog::query()
                ->where('slug', $slug)
                ->when($ignoreId, static fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function csvInput(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode(',', array_filter($value));
        }

        return filled($value) ? (string) $value : null;
    }

    private function isOwnedBlogImage(string $path): bool
    {
        return $path !== ''
            && Str::startsWith($path, 'upload/images/blog/')
            && ! Str::contains($path, ['..', "\0", '\\']);
    }
}
