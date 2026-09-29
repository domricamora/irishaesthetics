<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Faq;
use App\Models\MembershipTier;
use App\Models\Page;
use App\Models\Promotion;
use App\Models\Specialist;
use App\Models\Testimonial;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        // Only what the page still renders. The specialist and FAQ queries
        // were dropped along with those sections; both remain on the pages
        // that do show them, so the data is not gone, only not fetched here.
        return Inertia::render('home', [
            'categories' => TreatmentCategory::orderBy('sort')->withCount(['treatments'])->get(['id', 'name', 'slug', 'description', 'image']),
            'featured' => $this->activeTreatments()->where('is_featured', true)->get(),
            'bookable' => $this->activeTreatments()->get(),
            'branches' => $this->branches(),
            'testimonials' => Testimonial::where('is_published', true)->latest('id')->get(['id', 'author_name', 'author_meta', 'quote', 'rating']),
        ]);
    }

    public function treatments(): Response
    {
        return Inertia::render('treatments/index', [
            'categories' => TreatmentCategory::orderBy('sort')->with(['treatments' => fn ($q) => $q->select(['id', 'treatment_category_id', 'name', 'slug', 'summary', 'duration_minutes', 'price', 'promo_price', 'image', 'recommended_sessions'])])->get(['id', 'name', 'slug', 'description', 'image']),
        ]);
    }

    public function treatment(string $slug): Response
    {
        $treatment = Treatment::where('slug', $slug)->where('is_active', true)->with('category:id,name,slug')->firstOrFail();

        return Inertia::render('treatments/show', [
            'treatment' => $treatment,
            'related' => Treatment::where('treatment_category_id', $treatment->treatment_category_id)->where('id', '!=', $treatment->id)->where('is_active', true)->orderBy('sort')->limit(3)->get(['id', 'name', 'slug', 'summary', 'price', 'promo_price', 'image', 'duration_minutes']),
            'specialists' => $this->specialists(),
        ]);
    }

    /** About: who the clinic is, how it works, who looks after you (plan.md §2). */
    public function about(): Response
    {
        return Inertia::render('about', [
            'specialists' => $this->specialists(everyone: true),
            'branches' => $this->branches(),
            'testimonials' => Testimonial::where('is_published', true)->latest('id')->limit(3)->get(['id', 'author_name', 'author_meta', 'quote', 'rating']),
            'faqs' => Faq::orderBy('sort')->limit(4)->get(['id', 'question', 'answer']),
        ]);
    }

    /** Membership: the monthly tiers and what they include (plan.md §36). */
    public function membership(): Response
    {
        return Inertia::render('membership', [
            'tiers' => MembershipTier::where('is_active', true)->orderBy('sort')->get(['id', 'name', 'slug', 'tagline', 'price_monthly', 'benefits', 'note', 'is_featured']),
            'branches' => $this->branches(),
            'bookable' => $this->activeTreatments()->get(),
            'faqs' => Faq::orderBy('sort')->get(['id', 'question', 'answer']),
        ]);
    }

    /** Promotions and packages, each with what it includes and when it ends. */
    public function promotions(): Response
    {
        return Inertia::render('promotions', [
            'promotions' => Promotion::where('is_active', true)->orderBy('sort')
                ->with('treatment:id,name,slug,price,promo_price')
                ->get(['id', 'title', 'slug', 'summary', 'description', 'details', 'badge', 'ends_on', 'image', 'treatment_id']),
            'tiers' => MembershipTier::where('is_active', true)->orderBy('sort')->get(['id', 'name', 'price_monthly', 'tagline']),
        ]);
    }

    /** Before and after: illustrative comparisons, never presented as results (plan.md §18). */
    public function beforeAfter(): Response
    {
        return Inertia::render('before-after', [
            'cases' => $this->activeTreatments()->where('is_featured', true)->get()
                ->map(fn (Treatment $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'summary' => $t->summary,
                    'image' => $t->image,
                    'category' => $t->category?->name,
                ]),
        ]);
    }

    /** Contact: branches, hours and one form that lands in the CRM. */
    public function contact(): Response
    {
        return Inertia::render('contact', [
            'branches' => Branch::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug', 'address', 'city', 'phone', 'email', 'image', 'hours', 'map_url']),
            'treatments' => Treatment::where('is_active', true)->orderBy('sort')->get(['id', 'name']),
        ]);
    }

    /** Privacy policy, terms and the data privacy notice, edited in the database. */
    public function page(string $page): Response
    {
        $page = Page::where('slug', $page)->firstOrFail();

        return Inertia::render('legal', [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'summary' => $page->summary,
                'sections' => $page->sections,
                'reviewed_on' => $page->reviewed_on?->toDateString(),
            ],
            'others' => Page::where('id', '!=', $page->id)->orderBy('id')->get(['id', 'title', 'slug']),
        ]);
    }

    /** @return Builder<Treatment> */
    private function activeTreatments()
    {
        return Treatment::query()->where('is_active', true)->orderBy('sort')
            ->with('category:id,name,slug')
            ->select(['id', 'treatment_category_id', 'name', 'slug', 'summary', 'duration_minutes', 'price', 'promo_price', 'image', 'recommended_sessions', 'is_featured']);
    }

    /** @return Collection<int, array<string, mixed>> */
    /**
     * The people a client can see (plan.md §2). Specialists are the clinical
     * profiles; anyone on the staff roll can be published as well, because a
     * clinic's front desk is part of who looks after you. Publishing is opt-in
     * per person, and nobody appears twice when a specialist is also staff.
     *
     * @return Collection<int, array<string, mixed>>
     */
    /**
     * The people shown on the public site.
     *
     * The staff roll is the source of truth, because it is the only one an
     * administrator can edit: a name, a photo, a title or a line of copy is
     * changed in the office rather than in a seeder. Only somebody with
     * "show on the website" ticked appears, and a resigned person appears
     * nowhere.
     *
     * A clinic may also have a visiting specialist who is not on the payroll
     * at all, and those still need somewhere to be listed, so a specialist
     * record that matches nobody on the staff roll is kept. Where the two do
     * describe the same person, the staff record wins -- the previous order
     * had it the other way round, which meant the site's roster was the
     * seeded copy and every edit made in the office was silently ignored.
     */
    private function specialists(bool $everyone = false)
    {
        $staff = Employee::where('show_on_site', true)
            ->where('status', '!=', 'resigned')
            ->when(! $everyone, fn ($q) => $q->where('practitioner', true))
            ->with('branch:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Employee $e) => [
                'id' => 'staff-'.$e->id,
                'name' => $e->name,
                'slug' => null,
                'title' => $e->position,
                'credentials' => $e->credentials,
                'bio' => $e->notes ?: $e->focus,
                'photo' => $e->photo,
                'focus' => $e->focus ? [$e->focus] : [],
                'branches' => $e->branch ? [$e->branch->name] : [],
            ]);

        $onRoll = $staff->pluck('name')->map(fn (string $name) => mb_strtolower($name));

        $visitors = Specialist::where('is_active', true)
            ->orderBy('sort')
            ->with('branches:id,name')
            ->get()
            ->reject(fn (Specialist $s) => $onRoll->contains(mb_strtolower($s->name)))
            ->map(fn (Specialist $s) => $s->only(['id', 'name', 'slug', 'title', 'credentials', 'bio', 'photo', 'focus']) + ['branches' => $s->branches->pluck('name')]);

        return $staff->concat($visitors)->values();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Branch> */
    private function branches()
    {
        return Branch::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug', 'address', 'city', 'phone', 'email', 'image', 'hours']);
    }
}
