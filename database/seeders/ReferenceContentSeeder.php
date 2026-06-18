<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PortfolioProject;
use App\Models\PricingTier;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Models\SiteSetting;
use App\Models\Task;
use App\Models\TaskColumn;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->settings();
        $this->pages();
        $this->services();
        $this->portfolio();
        $this->gallery();
        $this->socialProof();
        $this->products();
        $this->formsAndOperations();
        $this->tasks();
    }

    private function settings(): void
    {
        foreach ([
            'company' => [
                'name'        => 'Tej Printbrands',
                'tagline'     => 'Creative Design & Printing Solutions',
                'description' => 'Premium graphic design, printing, and branding solutions that help your business stand out.',
                'currency'    => 'KES',
                'tax_rate'    => 16,
            ],
            'contact' => [
                'email'         => 'info@tejprintbrands.com',
                'support_email' => 'support@tejprintbrands.com',
                'phone'         => '+254 700 000 000',
                'address'       => 'Westlands Commercial Center, Nairobi, Kenya',
                'hours'         => 'Mon–Fri, 8am – 5pm',
            ],
            'socials' => [
                'facebook'  => '#',
                'instagram' => '#',
                'twitter'   => '#',
                'linkedin'  => '#',
            ],
            'work_stats' => [
                ['value' => '500+', 'label' => 'Projects Completed'],
                ['value' => '50+',  'label' => 'Industry Awards'],
                ['value' => '99%',  'label' => 'Client Satisfaction'],
                ['value' => '10+',  'label' => 'Years Experience'],
            ],
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function pages(): void
    {
        $pages = [
            'home' => ['title' => 'Tej Printbrands', 'subtitle' => 'Creative Design, Printing & Branding'],
            'services' => ['title' => 'Our Premium Services', 'subtitle' => 'Comprehensive design, printing, and branding solutions tailored to elevate your business.'],
            'work' => ['title' => 'Our Portfolio', 'subtitle' => 'A showcase of our finest work across various industries.'],
            'shop' => ['title' => 'Shop Print & Branding', 'subtitle' => 'Premium quality custom merchandise, apparel, and marketing materials ready to be branded with your logo.'],
            'gallery' => ['title' => 'Visual Inspiration Gallery', 'subtitle' => 'A curated collection of our finest design, printing, and branding work.'],
            'contact' => ['title' => 'Get in Touch', 'subtitle' => 'We are here to help bring your ideas to life.'],
        ];

        foreach ($pages as $index => $page) {
            $model = SitePage::updateOrCreate(
                ['slug' => is_string($index) ? $index : $page['slug']],
                array_merge($page, ['sort_order' => array_search(is_string($index) ? $index : $page['slug'], array_keys($pages), true)])
            );

            $this->seedPageSections($model);
        }
    }

    private function seedPageSections(SitePage $page): void
    {
        $sections = [
            'home' => [
                ['hero', [
                    'heading' => 'Creative Design, Professional Printing & Business Branding Solutions.',
                    'subtext'  => 'Tej Printbrands is your trusted partner in turning ideas into impactful visual solutions. From concept to final print, we deliver excellence.',
                    'settings' => [
                        'cta_primary'   => 'Request a Quote',
                        'cta_secondary' => 'Book a Service',
                        'cta_tertiary'  => 'View Our Work',
                    ],
                ]],
                ['services',     ['heading' => 'Our Services',         'subtext' => 'From concept to completion, we deliver excellence across all our creative services.']],
                ['portfolio',    ['heading' => 'Our Work',             'subtext' => 'A showcase of our recent projects. We take pride in delivering exceptional quality across all our services.']],
                ['testimonials', ['heading' => 'Client Testimonials',  'subtext' => null]],
                ['blog',         ['heading' => 'From Our Blog',          'subtext' => 'Tips, insights and updates from the Tej Printbrands team.']],
                ['brands',       ['heading' => "Brands We've Worked With", 'subtext' => null]],
            ],
            'services' => [
                ['hero', [
                    'heading' => 'Our Premium Services',
                    'subtext'  => 'Comprehensive design, printing, and branding solutions tailored to elevate your business.',
                    'settings' => ['cta' => 'Contact Us Today'],
                ]],
                ['process',  ['heading' => 'Our Process',       'subtext' => null]],
                ['pricing',  ['heading' => 'Pricing Packages',  'subtext' => 'Transparent pricing tailored to businesses of all sizes.']],
            ],
            'work' => [
                ['hero', [
                    'heading' => 'Our Best Work',
                    'subtext'  => 'Explore our portfolio of successful projects, from stunning brand identities to large-scale environmental graphics.',
                ]],
                ['case-studies', ['heading' => 'Featured Case Studies', 'subtext' => null]],
                ['portfolio',    ['heading' => 'Our Work',              'subtext' => 'A showcase of our recent projects. We take pride in delivering exceptional quality across all our services.']],
            ],
            'shop' => [
                ['hero', [
                    'heading' => 'Premium Print Products',
                    'subtext'  => 'Select a polished package, customize the finish, and checkout with M-Pesa, bank transfer, or card-style payment intake.',
                ]],
                ['products', [
                    'heading' => 'Curated Premium Packages',
                    'subtext'  => 'Designed for businesses that want print work to feel intentional, tactile, and boardroom-ready.',
                    'settings' => ['quote_label' => 'Need a custom quote?'],
                ]],
            ],
            'gallery' => [
                ['hero', [
                    'heading' => 'Visual Inspiration Gallery',
                    'subtext'  => 'A curated collection of our finest design, printing, and branding work.',
                ]],
            ],
            'contact' => [
                ['hero',         ['heading' => 'Get in Touch',                   'subtext' => "We're here to help bring your ideas to life. Reach out to us for quotes, consultations, or general inquiries."]],
                ['contact-info', ['heading' => 'Contact Information',            'subtext' => null]],
                ['faqs',         ['heading' => 'Frequently Asked Questions',     'subtext' => null]],
                ['form',         ['heading' => 'Send a Message',                 'subtext' => null]],
            ],
        ];

        foreach ($sections[$page->slug] ?? [] as [$key, $values]) {
            $this->section($page, $key, $values);
        }
    }

    private function section(SitePage $page, string $key, array $values): void
    {
        SiteSection::updateOrCreate(
            ['site_page_id' => $page->id, 'key' => $key],
            array_merge(['sort_order' => $page->sections()->count()], $values)
        );
    }

    private function services(): void
    {
        $services = [
            ['Graphic Design', 'Stunning visual concepts that communicate your brand message effectively and beautifully.', 'palette', 'KES 15,000', ['Logo & Identity Design', 'Brand Guidelines', 'Marketing Collateral', 'Packaging Design'], 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&h=600&fit=crop'],
            ['Printing', 'High-quality digital and offset printing services for all your business needs.', 'printer', 'KES 5,000', ['Business Cards & Stationery', 'Brochures & Flyers', 'Corporate Reports', 'Large Format Printing'], 'https://images.unsplash.com/photo-1562654501-a0ccc0fc3fb1?w=800&h=600&fit=crop'],
            ['Signage', 'Eye-catching indoor and outdoor signs that guide and attract your target audience.', 'signpost', 'KES 25,000', ['Storefront Signage', 'Wayfinding Systems', 'Window Graphics', 'Vehicle Wraps'], 'https://images.unsplash.com/photo-1563906267088-b029e7101114?w=800&h=600&fit=crop'],
            ['Promotional Items', 'Custom branded merchandise to keep your business top-of-mind with clients.', 'gift', 'KES 8,000', ['Custom Apparel', 'Corporate Gifts', 'Event Giveaways', 'Branded Tech Accessories'], 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=800&h=600&fit=crop'],
        ];

        foreach ($services as $index => [$title, $description, $icon, $price, $features, $image]) {
            Service::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
                'starting_price' => $price,
                'features' => $features,
                'sub_services' => array_slice($features, 0, 3),
                'image_url' => $image,
                'sort_order' => $index,
            ]);
        }

        foreach ([
            ['01', 'Discovery', 'We learn about your brand, goals, and specific project requirements.'],
            ['02', 'Design', 'Our creative team develops concepts and refines them based on your feedback.'],
            ['03', 'Production', 'Using state-of-the-art equipment, we bring the approved designs to life.'],
            ['04', 'Delivery', 'Quality checked and delivered to your door or installed on-site.'],
        ] as $index => [$number, $title, $description]) {
            ProcessStep::updateOrCreate(['number' => $number], compact('number', 'title') + ['description' => $description, 'sort_order' => $index]);
        }

        foreach ([
            ['Starter', 'From KES 15,000', null, false, 'Perfect for small businesses needing essential branding and print materials.', ['Basic Logo Design', '100 Business Cards', 'Digital Letterhead', '2 Revisions'], 23],
            ['Professional', 'From KES 45,000', 'KES 60,000', true, 'Comprehensive solutions for growing companies looking to make an impact.', ['Complete Brand Identity', '500 Premium Business Cards', 'Company Profile Design', 'Basic Signage', 'Unlimited Revisions'], 87],
            ['Enterprise', 'Custom Quote', null, false, 'Full-scale branding, printing, and environmental design for large organizations.', ['Dedicated Account Manager', 'Fleet Vehicle Wraps', 'Full Storefront Signage', 'Custom Merchandise', 'Priority Production'], 12],
        ] as $index => [$name, $price, $old, $popular, $description, $features, $orders]) {
            PricingTier::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'price' => $price,
                'old_price' => $old,
                'is_popular' => $popular,
                'description' => $description,
                'features' => $features,
                'orders_count' => $orders,
                'sort_order' => $index,
            ]);
        }
    }

    private function portfolio(): void
    {
        $items = [
            ['Business Card Design', 'Graphic Design', 'Meridian Tech Solutions', 'January 2024', 'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&h=400&fit=crop'],
            ['Corporate Brochure', 'Printing', 'Greenfield Industries', 'February 2024', 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600&h=400&fit=crop'],
            ['Restaurant Menu Design', 'Graphic Design', 'Bella Cucina Restaurant', 'March 2024', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop'],
            ['Storefront Signage', 'Signage', 'Urban Brew Coffee', 'April 2024', 'https://images.unsplash.com/photo-1528698827591-e19cef791f48?w=600&h=400&fit=crop'],
            ['Brand Identity Package', 'Branding', 'Nova Wellness Studio', 'May 2024', 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop'],
            ['Event Banners', 'Printing', 'TechConnect Summit', 'June 2024', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=400&fit=crop'],
            ['Vehicle Wrap Design', 'Branding', 'Swift Delivery Co.', 'July 2024', 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=600&h=400&fit=crop'],
            ['Product Packaging', 'Graphic Design', 'Artisan Harvest Foods', 'August 2024', 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=600&h=400&fit=crop'],
            ['Billboard Design', 'Signage', 'Apex Financial Group', 'September 2024', 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&h=400&fit=crop'],
            ['T-Shirt Printing', 'Printing', 'Coastal Music Festival', 'October 2024', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=400&fit=crop'],
            ['Logo Design', 'Branding', 'Pinnacle Architecture', 'November 2024', 'https://images.unsplash.com/photo-1626785774625-ddcddc3445e9?w=600&h=400&fit=crop'],
            ['Window Graphics', 'Signage', 'Zenith Fitness Center', 'December 2024', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&h=400&fit=crop'],
        ];

        $caseStudies = [
            'brand-identity-package' => [
                'is_case_study' => true,
                'challenge'     => 'Nova Wellness needed a modern identity that reflected their holistic approach while standing out in a saturated market.',
                'solution'      => 'We developed a calming, organic visual identity system, applied across digital platforms, interior signage, and premium printed collateral.',
                'results'       => ['40% increase in new memberships', 'Award-winning interior signage', 'Cohesive brand presence'],
            ],
            'vehicle-wrap-design' => [
                'is_case_study' => true,
                'challenge'     => 'Swift Delivery required a high-impact vehicle wrap design for their new fleet of 50 vans to maximize city-wide visibility.',
                'solution'      => 'A bold, dynamic wrap design using reflective vinyl materials for day/night visibility, paired with clear, memorable messaging.',
                'results'       => ['Millions of daily impressions', 'Consistent fleet appearance', 'Durable 5-year wrap lifespan'],
            ],
        ];

        foreach ($items as $index => [$title, $category, $client, $date, $image]) {
            $slug  = Str::slug($title);
            $extra = $caseStudies[$slug] ?? ['is_case_study' => false, 'challenge' => null, 'solution' => null, 'results' => null];
            PortfolioProject::updateOrCreate(['slug' => $slug], array_merge([
                'title'        => $title,
                'category'     => $category,
                'client'       => $client,
                'project_date' => $date,
                'image_url'    => $image,
                'description'  => "A polished {$category} project delivered for {$client}, combining premium materials, precise execution, and a strong brand outcome.",
                'services'     => [$category, 'Design', 'Production'],
                'gallery'      => [
                    'https://images.unsplash.com/photo-1572044162444-ad60f128bdea?w=600&h=400&fit=crop',
                    'https://images.unsplash.com/photo-1531973576160-7125cd663d86?w=600&h=400&fit=crop',
                ],
                'sort_order'   => $index,
            ], $extra));
        }
    }

    private function gallery(): void
    {
        $urls = [
            ['Branding', 'https://images.unsplash.com/photo-1626785774625-ddcddc3445e9?w=800&h=600&fit=crop', 'md:col-span-2 row-span-2'],
            ['Printing', 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600&h=400&fit=crop', ''],
            ['Branding', 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=800&fit=crop', 'row-span-2'],
            ['Graphic Design', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop', ''],
            ['Signage', 'https://images.unsplash.com/photo-1528698827591-e19cef791f48?w=800&h=400&fit=crop', 'md:col-span-2'],
            ['Printing', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=600&fit=crop', ''],
            ['Branding', 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=800&fit=crop', 'md:col-span-2 row-span-2'],
            ['Graphic Design', 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=600&h=400&fit=crop', ''],
            ['Signage', 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&h=800&fit=crop', 'row-span-2'],
            ['Printing', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=400&fit=crop', ''],
        ];

        foreach ($urls as $index => [$category, $url, $span]) {
            GalleryItem::updateOrCreate(['image_url' => $url], [
                'title' => "{$category} gallery item",
                'category' => $category,
                'span' => $span,
                'file_size' => rand(16, 51) / 10 . ' MB',
                'uploaded_at' => now()->subDays($index + 1),
                'sort_order' => $index,
            ]);
        }
    }

    private function socialProof(): void
    {
        foreach ([
            ['Michael R.', 'Startup Founder', 'Amazing quality and fast service! The business cards came out exactly how we envisioned.', 5, 'approved'],
            ['Sarah L.', 'Marketing Director', 'Tej Printbrands transformed our brand. Highly recommended! Their attention to detail is unmatched.', 5, 'approved'],
            ['David K.', 'Logistics Manager', 'Professional team with incredible attention to detail. The vehicle wrap looks stunning.', 5, 'approved'],
            ['Linda M.', 'Restaurant Owner', 'They redesigned our menus beautifully. We got compliments from customers from day one.', 4, 'pending'],
        ] as $index => [$name, $role, $text, $rating, $status]) {
            Testimonial::updateOrCreate(['name' => $name, 'text' => $text], compact('name', 'role', 'text', 'rating', 'status') + ['submitted_at' => now()->subDays($index + 1)]);
        }

        foreach ([
            ['Brandix',    'text-emerald-500', 'brandix.com',    'Retail & FMCG'],
            ['Nexacorp',   'text-blue-600',    'nexacorp.com',   'Technology'],
            ['Ecohome',    'text-green-500',   'ecohome.co.ke',  'Real Estate'],
            ['Urbanstyle', 'text-orange-500',  'urbanstyle.com', 'Fashion & Lifestyle'],
        ] as $index => [$name, $color, $domain, $industry]) {
            Brand::updateOrCreate(['name' => $name], compact('color', 'domain', 'industry') + ['sort_order' => $index]);
        }

        foreach ([
            ['What is your typical turnaround time?', 'Standard printing takes 3-5 business days. Custom design projects vary from 1-4 weeks depending on complexity. Rush services are available upon request.'],
            ['Do you offer international shipping?', 'Currently, we primarily serve local and national clients, but we can arrange international shipping for large corporate orders on a case-by-case basis.'],
            ['What file formats do you accept for printing?', 'We prefer high-resolution PDF files with bleed and crop marks. We also accept AI, EPS, PSD, and high-res TIFF/JPEG files.'],
            ['Can you help with the design if I only have an idea?', 'Absolutely! Our in-house graphic design team specializes in bringing concepts to life.'],
        ] as $index => [$question, $answer]) {
            Faq::updateOrCreate(['question' => $question], ['answer' => $answer, 'sort_order' => $index]);
        }
    }

    private function products(): void
    {
        $products = [
            // Premium showcase packages (displayed on the shop page)
            ['Executive Business Cards', 'Premium Stationery', 3500, 'Thick matte cards with crisp color, soft-touch finishing, and optional foil accents.', 'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=900&h=650&fit=crop', ['Finish' => ['Matte Lamination', 'Soft Touch', 'Gold Foil Accent']]],
            ['Luxury Company Profile', 'Corporate Print', 18000, 'A refined company profile package designed and printed for serious boardroom presence.', 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=900&h=650&fit=crop', ['Finish' => ['Saddle Stitch', 'Perfect Bind', 'Spot UV Cover']]],
            ['Premium Signage Kit', 'Signage', 45000, 'A polished storefront starter package for visibility, wayfinding, and brand impact.', 'https://images.unsplash.com/photo-1563906267088-b029e7101114?w=900&h=650&fit=crop', ['Finish' => ['Acrylic Face', 'Aluminium Composite', 'Illuminated Upgrade']]],
            ['Branded Merchandise Box', 'Corporate Gifts', 12500, 'Curated branded merchandise boxes for client gifts, team onboarding, and events.', 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=900&h=650&fit=crop', ['Finish' => ['Classic Box', 'Executive Box', 'Event Box']]],
            // General catalogue
            ['Premium Cotton T-Shirt', 'Apparel', 1200, 'High-quality 100% cotton t-shirt perfect for custom branding.', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&q=80', ['Size' => ['S', 'M', 'L', 'XL', 'XXL']]],
            ['Matte Finish Business Cards (100pcs)', 'Stationery', 1500, 'Premium 350gsm matte finish business cards with double-sided full-color printing.', 'https://images.unsplash.com/photo-1589041127535-40c25d722366?auto=format&fit=crop&q=80', []],
            ['Branded Ceramic Mug', 'Mugs & Drinkware', 850, 'Classic 11oz ceramic mug with vibrant full-color sublimation printing.', 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?auto=format&fit=crop&q=80', []],
            ['Pull-up Banner (Broadbase)', 'Banners & Signage', 8500, 'Professional 85x200cm pull-up banner with a sturdy broadbase aluminum stand.', 'https://images.unsplash.com/photo-1629429408209-1f912961dbd8?auto=format&fit=crop&q=80', []],
            ['Custom A5 Notebooks (50pcs)', 'Stationery', 12500, 'Hardcover A5 notebooks with your logo debossed or printed on the cover.', 'https://images.unsplash.com/photo-1531346878377-a541e4a115fc?auto=format&fit=crop&q=80', []],
            ['Branded Hoodie', 'Apparel', 2500, 'Warm fleece hoodie with premium embroidery or screen printing for your brand logo.', 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&q=80', ['Size' => ['M', 'L', 'XL']]],
            ['Canvas Tote Bag', 'Promotional Items', 600, 'Eco-friendly 100% cotton canvas tote bag.', 'https://images.unsplash.com/photo-1597484661643-2f5fef640df1?auto=format&fit=crop&q=80', []],
            ['Custom Vinyl Stickers (500pcs)', 'Promotional Items', 3500, 'Die-cut waterproof vinyl stickers with UV resistance.', 'https://images.unsplash.com/photo-1584448141569-69f342da535c?auto=format&fit=crop&q=80', []],
        ];

        foreach ($products as $index => [$name, $category, $price, $description, $image, $options]) {
            $cat = ProductCategory::firstOrCreate(['slug' => Str::slug($category)], ['name' => $category, 'sort_order' => $index]);
            $product = Product::updateOrCreate(['slug' => Str::slug($name)], [
                'product_category_id' => $cat->id,
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'image_url' => $image,
                'rating' => 4.8,
                'features' => [],
                'sort_order' => $index,
            ]);
            foreach ($options as $optionName => $choices) {
                ProductOption::updateOrCreate(['product_id' => $product->id, 'name' => $optionName], ['choices' => $choices]);
            }
        }
    }

    private function formsAndOperations(): void
    {
        foreach ([
            ['James Mwangi', 'james.m@apex.co.ke', '+254 712 345 678', 'Quote Request', 'I need 1000 business cards printed for my new venture.', 'new'],
            ['Sarah Wanjiku', 'sarah@nova-wellness.com', '+254 722 456 789', 'Service Booking', 'We need to discuss extending the relationship for marketing collateral.', 'replied'],
            ['Michael Otieno', 'mike.o@coastal-music.com', '+254 733 567 890', 'Partnership', 'Interested in becoming our official print partner for the upcoming festival.', 'new'],
        ] as [$name, $email, $phone, $subject, $message, $status]) {
            ContactMessage::updateOrCreate(['email' => $email, 'subject' => $subject], [
                'first_name' => Str::before($name, ' '),
                'last_name' => Str::after($name, ' '),
                'name' => $name,
                'phone' => $phone,
                'message' => $message,
                'status' => $status,
            ]);
        }

        foreach ([
            ['REQ-2024-089', 'Apex Financial Group', 'james.m@apex.co.ke', 'Brand Identity Package', 'KES 100,000 - 200,000', '4-6 weeks', 'high', 'new'],
            ['REQ-2024-088', 'Nova Wellness', 'sarah@nova-wellness.com', 'Marketing Collateral', 'KES 50,000 - 100,000', '2-3 weeks', 'medium', 'in-review'],
            ['REQ-2024-087', 'Coastal Music Festival', 'mike.o@coastal-music.com', 'Custom Apparel', 'KES 200,000+', '6-8 weeks', 'high', 'quoted'],
        ] as [$number, $client, $email, $service, $budget, $timeline, $priority, $status]) {
            ServiceRequest::updateOrCreate(['request_number' => $number], compact('client', 'email', 'service', 'budget', 'timeline', 'priority', 'status') + ['description' => "Request for {$service}."]);
        }

        foreach ([
            ['BKG-442', 'James Mwangi', 'james.m@apex.co.ke', '+254 712 345 678', 'Branding Consultation', 'confirmed', 25000],
            ['BKG-589', 'Sarah Wanjiku', 'sarah@nova-wellness.com', '+254 722 456 789', 'Design Review', 'pending', 45000],
        ] as $index => [$number, $client, $email, $phone, $service, $status, $price]) {
            ServiceBooking::updateOrCreate(['booking_number' => $number], [
                'client' => $client,
                'email' => $email,
                'phone' => $phone,
                'service' => $service,
                'preferred_date' => now()->addDays($index + 2)->toDateString(),
                'preferred_time' => '10:00',
                'duration' => '1 hour',
                'location' => $index === 0 ? 'In-person' : 'Video Call',
                'status' => $status,
                'price' => $price,
            ]);
        }

        foreach ([
            ['QT-2024-089', 'Meridian Tech', 'client@meridian.test', 'Brand Identity Package', 150000, 'approved'],
            ['QT-2024-088', 'Greenfield Ind.', 'hello@greenfield.test', 'Brochure Printing', 45000, 'pending'],
        ] as [$number, $client, $email, $service, $total, $status]) {
            $quote = Quotation::updateOrCreate(['quote_number' => $number], compact('client', 'email', 'service', 'total', 'status') + ['subtotal' => (int) round($total / 1.16), 'tax' => $total - (int) round($total / 1.16), 'terms' => 'Valid for 14 days.']);
            QuotationItem::updateOrCreate(['quotation_id' => $quote->id, 'description' => $service], ['quantity' => 1, 'unit_price' => $total, 'total' => $total]);
        }

        foreach ([
            ['INV-2024-042', 'Meridian Tech', 150000, 'paid', 'Bank Transfer'],
            ['INV-2024-041', 'Greenfield Ind.', 45000, 'unpaid', 'Pending'],
        ] as [$number, $client, $amount, $status, $method]) {
            Invoice::updateOrCreate(['invoice_number' => $number], ['client' => $client, 'amount' => $amount, 'paid_amount' => $status === 'paid' ? $amount : 0, 'status' => $status, 'due_date' => now()->addDays(14), 'payment_method' => $method]);
        }

        foreach ([
            ['PAY-1042', 'INV-2024-042', 'Meridian Tech', 150000, 'Bank Transfer', 'TRX-987654321'],
            ['PAY-1041', null, 'Urban Brew', 42500, 'M-Pesa', 'SDF9876GHJ'],
        ] as [$number, $invoiceNumber, $client, $amount, $method, $ref]) {
            Payment::updateOrCreate(['payment_number' => $number], ['invoice_id' => $invoiceNumber ? Invoice::where('invoice_number', $invoiceNumber)->value('id') : null, 'client' => $client, 'amount' => $amount, 'method' => $method, 'reference' => $ref, 'paid_at' => now()->subDays(2)]);
        }
    }

    private function tasks(): void
    {
        foreach ([['todo', 'To Do'], ['in-progress', 'In Progress'], ['completed', 'Completed']] as $index => [$slug, $title]) {
            $column = TaskColumn::updateOrCreate(['slug' => $slug], ['title' => $title, 'sort_order' => $index]);
            foreach ([
                'todo' => ['Design logo for Apex Corp', 'Print 500 business cards', 'Create signage mockup'],
                'in-progress' => ['Brand identity for Nova Studio', 'Event banner printing'],
                'completed' => ['Vehicle wrap for Swift Co.', 'Menu design for Bella Cucina'],
            ][$slug] as $taskIndex => $title) {
                Task::updateOrCreate(['task_column_id' => $column->id, 'title' => $title], [
                    'priority' => $slug === 'completed' ? 'completed' : ($taskIndex === 0 ? 'high' : 'medium'),
                    'due_date' => now()->addDays($taskIndex + 3),
                    'assignee' => ['JD', 'AM', 'SK'][$taskIndex % 3],
                    'sort_order' => $taskIndex,
                ]);
            }
        }
    }
}
