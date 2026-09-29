<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Treatment;
use Illuminate\Database\Seeder;

/**
 * Marketing content for the pages beyond the home screen: membership tiers,
 * promotions, journal articles and the legal pages. Copy is fictional demo
 * content and avoids result guarantees and superiority claims (plan.md §83).
 */
class MarketingContentSeeder extends Seeder
{
    /** slug => [name, tagline, monthly price, benefits, note, featured] */
    public const TIERS = [
        'glow' => [
            'Glow',
            'A monthly facial habit for steady, visible care.',
            2990,
            [
                'One Hydra Facial every month',
                '10% off all facial treatments',
                'Birthday treatment upgrade',
                'Priority booking windows',
            ],
            'Best for keeping up results after a treatment plan.',
            false,
        ],
        'premium' => [
            'Premium',
            'A monthly allowance, doctor reviews and member pricing.',
            6500,
            [
                'A monthly treatment allowance of 7,500 pesos',
                '15% off treatments and products',
                'Quarterly skin review with a doctor',
                'Priority booking and member events',
                'Two guest passes a year',
            ],
            'Unused allowance carries over for 30 days.',
            true,
        ],
        'signature' => [
            'Signature',
            'Everything in Premium, plus plan reviews and concierge booking.',
            12500,
            [
                'A monthly allowance of 15,000 pesos',
                '20% off treatments and products',
                'Quarterly injectables plan review',
                'Dedicated line and same-day appointment requests',
                'An annual body or wellness session',
            ],
            'Injectable and clinical treatments still begin with a consultation.',
            false,
        ],
    ];

    /** slug => [title, summary, description, details, badge, ends_on, treatment, image] */
    public const PROMOTIONS = [
        'first-visit-consultation' => [
            'First visit consultation credited',
            'New here? The doctor consultation fee is credited to your first treatment.',
            'Every treatment at Irish starts with a short assessment, so you know what is being recommended and why before you commit. On a first visit we credit the consultation fee against your first treatment booked within 30 days.',
            [
                'Consultation fee of 1,500 pesos credited to your first treatment',
                'Includes a skin assessment and a written plan you keep',
                'One credit per client, at any branch',
            ],
            'New patients',
            null,
            null,
            '/media/photos/consultation.jpg',
        ],
        'hydra-facial-series' => [
            'Hydra Facial series pricing',
            'Three Hydra Facials booked together, priced per session.',
            'Hydra Facial works best as a rhythm rather than a one off. Book three sessions as a series and each visit is priced below the single session rate.',
            [
                '2,990 pesos per session when you book three',
                'Sessions stay valid for six months from the first visit',
                'Rebook with the front desk at the end of each visit',
            ],
            'Series pricing',
            null,
            'hydra-facial',
            '/media/photos/hydrafacial.jpg',
        ],
        'bridal-package' => [
            'Bridal package',
            'Trial, wedding day makeup and a facial in the weeks before.',
            'A calm run up to the wedding: a trial where we agree the look, a facial two to three weeks before, and wedding day makeup that holds from ceremony to reception.',
            [
                'Bridal trial with a personal makeup lesson',
                'Wedding day makeup for the bride at your venue',
                'One pre-wedding facial at any branch',
                'Metro Manila and Cebu City travel included',
            ],
            'Book six weeks ahead',
            null,
            'bridal-makeup',
            '/media/photos/bridal.jpg',
        ],
        'skin-booster-set' => [
            'Skin Booster set of three',
            'Three skin booster sessions, four weeks apart, booked as a set.',
            'Micro-injections of hyaluronic acid for hydration and glow, spaced four weeks apart so your doctor can review how your skin responds between sessions.',
            [
                'Three sessions of Skin Boosters at a set price',
                'Doctor review before each session',
                'Numbing cream included',
            ],
            'Runs to 31 December 2026',
            '2026-12-31',
            'skin-boosters',
            '/media/photos/booster.jpg',
        ],
    ];

    /** slug => [title, category, excerpt, body, takeaways, image, author, read minutes, days ago] */
    public const POSTS = [
        'choosing-your-first-facial' => [
            'Choosing your first facial',
            'Skin care',
            'A facial is not one treatment but a family of them. Here is how to pick the one that suits your skin and your calendar.',
            "Facials differ by how much they exfoliate, how much downtime they involve and how often they should be repeated. A Hydra Facial is the gentlest starting point: cleansing, extraction and serum infusion in about an hour, with no downtime. A chemical peel goes further, using controlled exfoliation to refresh tone and texture, and usually needs three to six sessions spaced a few weeks apart.\n\nThe useful question is not which facial is strongest but which one fits your skin right now. If you have active breakouts, a doctor-led acne programme combined with extraction and home care usually works better than a single brightening facial. If your concern is dullness or pigmentation, a series planned in advance will do more than repeated one-offs.\n\nBring your current routine to the consultation, including prescription creams and anything you use for acne or retinol. Your practitioner needs that picture to advise a plan you can actually follow, and to tell you honestly if the treatment you asked for is not the one you need.\n\nMost clients see a difference after one visit and a clearer difference after a planned series. We will give you the plan in writing, with the number of sessions and the price, before you commit to anything.",
            [
                'Gentle first: a Hydra Facial suits most skin with no downtime',
                'Peels and lasers work as a series, not as a single visit',
                'Bring your current routine, including prescriptions, to the consultation',
            ],
            '/media/photos/facial.jpg',
            'Dr. Sofia Reyes',
            4,
            5,
        ],
        'what-happens-in-a-consultation' => [
            'What happens in a consultation',
            'Clinic news',
            'A first visit at Irish is an assessment, not a sales conversation. Here is how the forty minutes are spent.',
            "We start with what brought you in. Your practitioner asks about your concerns, how long you have had them, what you have tried and what you did not like about it. History matters: medicines, allergies, pregnancy or breastfeeding, recent treatments elsewhere, and any skin or health condition that changes what is safe.\n\nNext comes the assessment. Your skin is examined under good light, sometimes with a lamp or a camera, and your practitioner explains what they see in plain language. You are welcome to photograph anything you want to remember.\n\nThen the plan. Where a treatment is appropriate, you receive the options with prices, the number of sessions, realistic downtime and what aftercare involves. Where we think a treatment is not in your interest, we say so, and where a referral is more appropriate we will help you find one.\n\nThe consultation fee is 1,500 pesos, and on a first visit it is credited against your first treatment booked within 30 days.",
            [
                'Assessment first, treatment second',
                'You leave with a written plan and prices',
                'No treatment is booked on the day unless you ask for it',
            ],
            '/media/photos/consultation.jpg',
            'Dr. Adrian Santos',
            3,
            9,
        ],
        'aftercare-for-injectables' => [
            'Aftercare for injectables: the first 48 hours',
            'Aftercare',
            'Small habits in the first two days make the difference between a smooth result and an anxious week.',
            "For the rest of the day, keep your head up where you can and avoid lying face down. Skip strenuous exercise, saunas, steam rooms and hot yoga for 24 hours: heat and heavy exertion increase flushing and bruising.\n\nDo not massage or press the treated area unless your practitioner asked you to. Clean makeup is fine after 12 hours for most people, and gentle cleansing with cool water is better than a scrub or an active serum for the first two days.\n\nBruising is common and settles in three to seven days. A little tenderness, slight swelling or a mild headache after forehead treatment is also normal. Sleep on your back if you can, and keep to your normal skincare minus exfoliating acids and retinoids for 48 hours.\n\nCall us the same day if you notice severe pain, blanching of the skin, a spreading rash, blurred vision, difficulty swallowing or anything that worries you. We would much rather hear from you early.",
            [
                'No exercise, heat or alcohol for the first 24 hours',
                'Avoid massage and active skincare for 48 hours',
                'Call the clinic the same day about anything that worries you',
            ],
            '/media/photos/botox.jpg',
            'Dr. Adrian Santos',
            4,
            13,
        ],
        'sunscreen-in-manila' => [
            'Sunscreen in Manila: what actually matters',
            'Skin care',
            'Between the heat, the humidity and the commute, sunscreen here has to do more than sit in a bag.',
            "Broad spectrum matters more than the number. A sunscreen labelled SPF 50 but PA++ leaves you short on UVA, which is the part that drives pigmentation and ageing. Look for PA+++ or PA++++, or a label that says broad spectrum with a UVA rating.\n\nTexture decides whether you will use it. In this climate, a light gel or fluid for everyday and a heavier cream for air-conditioned days is a practical pair. Two fingers worth for the face and neck, reapplied every two to three hours outdoors, is the aim. Makeup with SPF is a bonus, not your main protection.\n\nTreatments raise the stakes. After a peel, a laser session or any injectable, your skin is more sensitive to the sun and to heat. Sunscreen in the morning, a hat at midday and shade where you can find it will protect the result you paid for.\n\nIf you are unsure which formula suits your skin, bring your current one to your next visit and we will look at it with you.",
            [
                'Broad spectrum with PA+++ or higher beats a big SPF number alone',
                'Reapply every two to three hours when you are outdoors',
                'After a treatment, sun protection protects the result',
            ],
            '/media/photos/skin-closeup.jpg',
            'Dr. Sofia Reyes',
            3,
            18,
        ],
        'between-laser-sessions' => [
            'Laser sessions: what to expect between visits',
            'Treatments',
            'Most of the improvement from a laser programme happens in the three weeks after each session, not during it.',
            "A laser session starts a repair process. In the days afterwards your skin may feel warm and look slightly flushed; pigmented areas can darken before they flake away. That is expected, and it is why sessions are spaced three to four weeks apart rather than stacked in one week.\n\nBetween visits, keep the routine boring: gentle cleansing, moisturiser, sunscreen in the morning and nothing new and active. Skip scrubs, waxing, threading and self-tanners on treated areas unless your practitioner says otherwise.\n\nTake a photograph in the same light and at the same distance each week. Progress in pigmentation and texture is gradual, and a set of photos taken the same way is far more reliable than memory on a tired evening.\n\nTell us about any trip, event or holiday planned in the next month, because sun exposure and laser programmes need to be planned around each other.",
            [
                'Sessions are spaced three to four weeks apart on purpose',
                'Keep skincare simple between visits',
                'Weekly photos in the same light show progress that memory misses',
            ],
            '/media/photos/laser.jpg',
            'Dr. Maya Navarro',
            4,
            22,
        ],
        'scalp-health-basics' => [
            'Scalp health basics for thinning hair',
            'Hair',
            'Hair programmes work better when they start with the scalp and with an honest look at the last six months.',
            "Thinning hair has many causes: genetics, hormonal shifts, thyroid function, iron levels, stress, weight loss, and styling or treatment habits that pull and heat the follicle. A consultation is about narrowing that list rather than guessing, and it may include blood tests or a referral.\n\nDay to day, wash when your scalp needs it rather than to a schedule, treat the scalp as skin that deserves care, and avoid tight styles, harsh chemical straightening and high heat close to the root. If your scalp itches, flakes or stings, that is worth treating rather than ignoring.\n\nProgrammes such as PRP or topical treatments are planned over months, not weeks, and progress is judged at three and six months with photographs. Anyone promising a fixed result in a fortnight is not describing how hair cycles work.\n\nStart with an assessment. It is the cheapest part of the journey and the one that decides everything after it.",
            [
                'Thinning hair has several possible causes; assessment narrows them',
                'Scalp comfort and gentle styling support every programme',
                'Judge hair progress at three and six months, with photos',
            ],
            '/media/photos/prp.jpg',
            'Dr. Maya Navarro',
            3,
            27,
        ],
    ];

    /** slug => [title, summary, reviewed days ago, sections] */
    public const PAGES = [
        'privacy-policy' => [
            'Privacy Policy',
            'How Irish Aesthetics and Beauty Lounge collects, uses, stores and protects personal information, in line with the Data Privacy Act of 2012 (Republic Act 10173).',
            0,
            [
                ['heading' => 'Who we are', 'paragraphs' => [
                    'Irish Aesthetics and Beauty Lounge operates aesthetic and wellness clinics in Makati, BGC and Cebu. We are the personal information controller for the information described in this policy.',
                    'Questions about privacy, or a request to exercise your rights, can be sent to the clinic email address on our contact page or raised at the branch where you are treated. We aim to respond within fifteen working days.',
                ]],
                ['heading' => 'What we collect', 'paragraphs' => [
                    'Identification and contact details you give us: your name, mobile number, email address, preferred branch and, where relevant, your birth date and gender.',
                    'Appointment and commercial records: treatments booked and attended, practitioner notes, prices paid, packages or memberships, and messages you send us.',
                    'Health information you share with our practitioners: your concerns, medical history, medicines, allergies, pregnancy or breastfeeding status, and consent records. This is sensitive personal information and we treat it with additional care.',
                    'Technical information from this website: the pages you visit, the campaign or link that brought you here, your approximate location from your IP address, and the device type you used.',
                ]],
                ['heading' => 'Why we collect it', 'paragraphs' => [
                    'To respond to your enquiry and arrange appointments you ask for.',
                    'To provide care safely: assessing your skin, planning treatment, recording what was done, and following up.',
                    'To run the clinic: scheduling, reminders, billing and receipts, membership administration, and inventory of products used.',
                    'To improve our services and, where you have agreed, to send offers and updates. Marketing consent is separate from the consent you give for treatment and can be withdrawn at any time.',
                ]],
                ['heading' => 'Legal basis', 'paragraphs' => [
                    'Where you are a client or patient, we rely mainly on the performance of our agreement with you and on the provision of health care, together with your consent for sensitive personal information and for marketing.',
                    'Where the law requires records to be kept, for example for tax or regulatory purposes, we process information to comply with those obligations. We also have a legitimate interest in keeping our clinics secure and our services working properly.',
                ]],
                ['heading' => 'Who we share it with', 'paragraphs' => [
                    'Our practitioners and staff who need the information to care for you or to run your visit.',
                    'Suppliers who help us operate, such as our booking and hosting providers and our payment channels, under contracts that limit their use of the information to the service they provide.',
                    'Professional advisers, auditors and, where we are required to do so, government agencies such as the Bureau of Internal Revenue and the Department of Health.',
                    'We do not sell personal information, and we do not share health information for advertising.',
                ]],
                ['heading' => 'How long we keep it', 'paragraphs' => [
                    'Enquiry records that do not become client records are kept for two years, then deleted.',
                    'Clinical records are kept for as long as the law and professional guidance require, which is generally at least ten years after your last visit, and longer for records involving minors.',
                    'Marketing contact details are kept until you withdraw consent or ask to be removed.',
                ]],
                ['heading' => 'How we protect it', 'paragraphs' => [
                    'Access to clinical information is limited by role, and staff accounts require strong passwords and, where available, a second factor. Activity on client records is logged.',
                    'Data is stored on access-controlled systems, transmitted over encrypted connections, and backed up. We review access and retention regularly and investigate any suspected breach.',
                    'If a breach occurs that is likely to give rise to serious harm, we will notify you and the National Privacy Commission as required by law.',
                ]],
                ['heading' => 'Your rights', 'paragraphs' => [
                    'You have the right to be informed, to object to processing, to access your information, to correct it, to have it erased or blocked in the circumstances allowed by law, to claim damages, to data portability, and to lodge a complaint with the National Privacy Commission.',
                    'To exercise any of these rights, contact us with your name, the branch you attend and what you would like us to do. We may ask for proof of identity before acting, and we may need to keep certain records where the law requires it.',
                ]],
                ['heading' => 'Cookies and attribution', 'paragraphs' => [
                    'This website uses a small session cookie so that forms work and so that we can remember which page you came from when you send an enquiry. It does not build a profile of you across other websites.',
                    'Where you arrive from a campaign link, we record the campaign, the landing page and the referrer alongside your enquiry so that we can answer you in context.',
                ]],
                ['heading' => 'Changes to this policy', 'paragraphs' => [
                    'We review this policy when our services or the law change. The version published here applies from the date shown at the end of the page, and material changes will be announced on this website.',
                ]],
            ],
        ],
        'terms' => [
            'Terms of Service',
            'The terms that apply when you use this website, book an appointment with Irish Aesthetics and Beauty Lounge, or hold a membership.',
            0,
            [
                ['heading' => 'Using this website', 'paragraphs' => [
                    'This website is provided so that you can learn about our treatments and book appointments. It is a demonstration site: the clinic, the practitioners, the prices and the reviews shown are fictional, and no real medical service is provided through it.',
                    'Please do not misuse the site, attempt to reach data that is not yours, or submit information that is false or that belongs to someone else.',
                ]],
                ['heading' => 'Bookings and confirmations', 'paragraphs' => [
                    'A booking request is confirmed only when you receive a confirmation with a reference number. Please arrive ten minutes early and tell us if you are running late; a late arrival may need to be shortened or moved so that other clients are not kept waiting.',
                    'We may need to change a practitioner or a room for your visit. We will tell you as soon as we can, and you are free to move the appointment instead.',
                ]],
                ['heading' => 'Fees, deposits and payments', 'paragraphs' => [
                    'Prices published on this site are starting prices for one session in Philippine pesos and include professional fees unless a treatment page says otherwise. A consultation may add a separate fee, which we credit against treatment where we advertise it.',
                    'Where a package or series is booked, the price and validity period are stated on the promotion page and on your receipt. Packages are transferable to another branch but not to another person without our agreement.',
                ]],
                ['heading' => 'Cancellations and no-shows', 'paragraphs' => [
                    'Please give us at least 24 hours notice to move or cancel a visit. Repeated late cancellations or no-shows may mean we ask for a deposit to hold future bookings.',
                    'Injected and clinical treatments may have their own cancellation or deposit terms. These are explained before you book.',
                ]],
                ['heading' => 'Treatment suitability and consent', 'paragraphs' => [
                    'Every treatment begins with an assessment. Your practitioner may decide that a treatment is not suitable for you, may ask for more information, or may refer you to another professional. You can stop or decline a treatment at any time.',
                    'You are responsible for giving us accurate health information, including medicines, allergies, pregnancy or breastfeeding, and recent treatment elsewhere, so that we can advise you safely.',
                ]],
                ['heading' => 'Nothing here is medical advice', 'paragraphs' => [
                    'The content on this website, including journal articles and treatment descriptions, is general information for an adult audience in the Philippines. It is not a diagnosis, a prescription or a substitute for a consultation.',
                    'No result is guaranteed. Outcomes vary with your skin, your history and how closely you follow the aftercare you are given.',
                ]],
                ['heading' => 'Memberships', 'paragraphs' => [
                    'Memberships are monthly, start on the day of purchase and renew until you cancel. Benefits listed for a tier apply at the branch or branches named on the membership page, and unused allowances carry over for the period stated on that tier.',
                    'If prices change, we will tell you before your next renewal and you may cancel without penalty.',
                ]],
                ['heading' => 'Content and intellectual property', 'paragraphs' => [
                    'The text, layout and photography on this site belong to the clinic or its licensors. Please do not reuse them commercially without written permission.',
                    'Photographs used on this demonstration site come from royalty free libraries and are credited where the licence requires it.',
                ]],
                ['heading' => 'Limits of liability', 'paragraphs' => [
                    'To the extent the law allows, we are not liable for indirect or consequential loss arising from use of this website or from a booking made through it. Nothing in these terms limits any right you have under Philippine consumer and health laws.',
                ]],
                ['heading' => 'Governing law and changes', 'paragraphs' => [
                    'These terms are governed by the laws of the Republic of the Philippines, and disputes are subject to the courts of the city where the branch you used is located.',
                    'We may update these terms; the version published here applies from the date shown at the end of the page.',
                ]],
            ],
        ],
        'data-privacy-notice' => [
            'Data Privacy Notice',
            'What you agree to when you give us your information at the clinic, and the choices you keep.',
            0,
            [
                ['heading' => 'Our commitment', 'paragraphs' => [
                    'Your health information is sensitive personal information under the Data Privacy Act of 2012. We collect only what we need to care for you, we share it only with the people involved in your care and the suppliers that make the clinic work, and we keep it only as long as the law requires.',
                ]],
                ['heading' => 'What you agree to when you book', 'paragraphs' => [
                    'When you send an enquiry or book an appointment, you agree that we may contact you about that enquiry by SMS, phone or email, and that we may keep a record of the visit and of the care you receive.',
                    'Marketing messages are a separate choice. You can tick or untick that box at any time, and saying no does not affect your treatment or any enquiry you send us.',
                ]],
                ['heading' => 'Sensitive personal information', 'paragraphs' => [
                    'Before a treatment we ask about your health: your concerns, medical history, medicines, allergies, pregnancy or breastfeeding, and previous aesthetic or medical treatments. We ask because it changes what is safe for you.',
                    'This information is stored with your clinical record, is visible only to practitioners and staff who need it, and is never used for advertising.',
                ]],
                ['heading' => 'Consent and how to withdraw it', 'paragraphs' => [
                    'You give consent for a specific treatment in writing or on screen at the clinic, after your practitioner has explained what it involves, what the realistic downtime is, and what alternatives exist.',
                    'You can withdraw consent for future treatment or for marketing at any time by telling any branch or writing to our clinic email. Withdrawing consent does not affect records we are required to keep, and it does not apply to care already provided.',
                ]],
                ['heading' => 'Sharing for your care', 'paragraphs' => [
                    'With your agreement, we may share parts of your record with another clinic, hospital or laboratory involved in your care, or with a doctor you ask us to coordinate with.',
                    'Where we are required by law to report to a government agency, we will tell you what we reported unless the law prohibits it.',
                ]],
                ['heading' => 'Retention and security', 'paragraphs' => [
                    'We keep clinical records for at least ten years after your last visit, and longer where the law requires, so that future practitioners can see your history. Afterwards they are securely destroyed.',
                    'Records are held in access-controlled systems with encrypted connections and backups. Access is logged, and we investigate any suspected breach and notify you where the law requires it.',
                ]],
                ['heading' => 'Your rights', 'paragraphs' => [
                    'You can ask what information we hold about you, ask for a copy, ask us to correct anything wrong, ask us to erase or block information where the law allows, object to particular processing, and ask for your information in a portable format.',
                    'You can also complain to the National Privacy Commission. Our team will help you exercise any of these rights; ask at the branch or write to the clinic email on our contact page.',
                ]],
            ],
        ],
    ];

    public function run(): void
    {
        $organization = Organization::where('slug', config('clinic.organization'))->firstOrFail();
        $org = ['organization_id' => $organization->id];

        $sort = 0;
        foreach (self::TIERS as $slug => [$name, $tagline, $price, $benefits, $note, $featured]) {
            MembershipTier::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'name' => $name, 'tagline' => $tagline, 'price_monthly' => $price, 'benefits' => $benefits,
                'note' => $note, 'is_featured' => $featured, 'sort' => $sort++,
            ]);
        }

        $sort = 0;
        foreach (self::PROMOTIONS as $slug => [$title, $summary, $description, $details, $badge, $endsOn, $treatment, $image]) {
            Promotion::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'description' => $description,
                'details' => $details,
                'badge' => $badge,
                'ends_on' => $endsOn,
                'treatment_id' => $treatment ? Treatment::withoutGlobalScopes()->where($org + ['slug' => $treatment])->value('id') : null,
                'image' => $image,
                'sort' => $sort++,
            ]);
        }

        foreach (self::POSTS as $slug => [$title, $category, $excerpt, $body, $takeaways, $image, $author, $minutes, $daysAgo]) {
            Post::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'category' => $category,
                'excerpt' => $excerpt,
                'body' => $body,
                'takeaways' => $takeaways,
                'image' => $image,
                'author_name' => $author,
                'read_minutes' => $minutes,
                'published_at' => now()->subDays($daysAgo),
            ]);
        }

        foreach (self::PAGES as $slug => [$title, $summary, $reviewedDaysAgo, $sections]) {
            Page::withoutGlobalScopes()->updateOrCreate($org + ['slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'sections' => $sections,
                'reviewed_on' => now()->subDays($reviewedDaysAgo),
            ]);
        }
    }
}
