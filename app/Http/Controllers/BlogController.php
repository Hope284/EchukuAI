<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Frontend\FrontendSectionsStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        $post->delete();

        return back()->with(['message' => __('Deleted Successfully'), 'type' => 'success']);
    }

    public function blogAddOrUpdateSave(Request $request)
    {
        $postId = $request->input('post_id');
        $isUpdate = filled($postId) && $postId !== 'undefined' && $postId !== 'null';

        $validator = Validator::make($request->all(), [
            'title'         => ['required', 'string', 'max:255'],
            'content'       => ['required', 'string'],
            'feature_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp'],
            'status'        => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $post = $isUpdate
            ? Blog::where('id', $postId)->firstOrFail()
            : new Blog;

        if ($request->hasFile('feature_image')) {
            $path = 'upload/images/blog/';
            $image = $request->file('feature_image');
            $baseSlug = Str::slug($request->input('slug') ?: $request->input('title'));
            $image_name = Str::random(4) . '-' . $baseSlug . '.' . $image->guessExtension();

            // Resim uzantı kontrolü
            $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            if (! in_array(Str::lower($image->guessExtension()), $imageTypes)) {
                $data = [
                    'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                ];

                return response()->json($data, 419);
            }

            File::ensureDirectoryExists(public_path($path));
            $image->move(public_path($path), $image_name);

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
        $post->save();

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
}
