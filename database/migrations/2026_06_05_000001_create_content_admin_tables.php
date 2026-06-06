<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password')->index();
        });

        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_page_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label')->nullable();
            $table->string('heading')->nullable();
            $table->text('subtext')->nullable();
            $table->string('image_url')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->unique(['site_page_id', 'key']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('image_url')->nullable();
            $table->string('starting_price')->nullable();
            $table->json('features')->nullable();
            $table->json('sub_services')->nullable();
            $table->boolean('is_featured')->default(true)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('process_steps', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('price');
            $table->string('old_price')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->string('color')->default('gray');
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->index();
            $table->string('client');
            $table->string('project_date')->nullable();
            $table->string('image_url');
            $table->text('description');
            $table->json('services')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_featured')->default(true)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('category')->index();
            $table->string('image_url');
            $table->string('span')->nullable();
            $table->string('file_size')->nullable();
            $table->timestamp('uploaded_at')->nullable()->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->text('text');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('status')->default('pending')->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->string('color')->nullable();
            $table->string('domain')->nullable();
            $table->string('industry')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('page_slug')->default('contact')->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->string('unit')->nullable();
            $table->text('description');
            $table->string('image_url');
            $table->decimal('rating', 2, 1)->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('choices');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Default');
            $table->string('recipient_name');
            $table->string('phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('county')->nullable();
            $table->string('country')->default('Kenya');
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('name')->index();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('subject')->index();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->boolean('is_starred')->default(false)->index();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('product');
            $table->string('quantity')->nullable();
            $table->string('size')->nullable();
            $table->string('artwork_path')->nullable();
            $table->string('delivery_method')->default('pickup')->index();
            $table->text('notes')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('service')->index();
            $table->date('preferred_date')->nullable()->index();
            $table->time('preferred_time')->nullable();
            $table->string('duration')->nullable();
            $table->string('location')->nullable();
            $table->string('budget')->nullable();
            $table->text('project_description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('price')->nullable();
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('client');
            $table->string('email')->index();
            $table->string('service')->index();
            $table->string('budget')->nullable();
            $table->string('timeline')->nullable();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('new')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->string('client');
            $table->string('email')->index();
            $table->string('service')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('tax')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('terms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('client');
            $table->string('email')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->string('status')->default('unpaid')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('payment_method')->default('Pending');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_number')->unique();
            $table->string('client')->index();
            $table->unsignedInteger('amount');
            $table->string('method')->index();
            $table->string('reference')->nullable()->index();
            $table->string('status')->default('completed')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('task_columns', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_column_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('priority')->default('medium')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('assignee')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_columns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_bookings');
        Schema::dropIfExists('quote_requests');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('portfolio_projects');
        Schema::dropIfExists('pricing_tiers');
        Schema::dropIfExists('process_steps');
        Schema::dropIfExists('services');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('site_sections');
        Schema::dropIfExists('site_pages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
