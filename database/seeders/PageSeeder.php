<?php
namespace Database\Seeders;

use App\Helpers\PageHelper;
use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Page::query()->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Page::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Page::truncate();
        }

        $languages = Language::query()->get()->keyBy('code');

        $pagesByLanguageGroupsFromStaticData = $this->getPagesByLanguageGroupsFromStaticData();
        foreach ($pagesByLanguageGroupsFromStaticData as $pagesByLanguageGroup) {
            $language = $languages[$pagesByLanguageGroup->language_code] ?? null;

            foreach ($pagesByLanguageGroup->pages as $page) {
                $isDefault    = false;
                $defaultUseAs = null;

                if (($page->title == "Home") || ($page->title == "হোম")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_HOME;
                }

                if (($page->title == "Latest") || ($page->title == "সর্বশেষ")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_LATEST;
                }

                if (($page->title == "Search") || ($page->title == "অনুসন্ধান")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_SEARCH;
                }

                Page::factory()->state([
                    'title'          => $page->title,
                    'brief'          => $page->brief ?? null,
                    'body'          => $page->body ?? null,

                    'language_id'    => $language?->id,
                    'seo_title'      => $page->title,
                    'seo_brief'      => $page->brief ?? null,
                    'seo_keywords'   => $page->seo_keywords ?? null,

                    'is_default'     => $isDefault,
                    'default_use_as' => $defaultUseAs ?? null,

                    'parent_id'      => null,

                    'title_tree'     => $page->title,
                    'slug_tree'      => Str::slug($page->title),

                    'is_published'   => true,
                ])->create();
            }
        }

    }

    private function getPagesByLanguageGroupsFromStaticData()
    {
        return collect([
            (object) [
                'language_code' => SeederHelper::LANGUAGE_EN_CODE,
                'pages'         => collect([
                    (object) [
                        'title'        => 'Home',
                        'brief'        => 'Welcome to our news portal with the latest updates, featured stories, and important headlines.',
                        'seo_keywords' => 'home, news portal, latest news, breaking news, top headlines',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'Latest',
                        'brief'        => 'Read the most recent news updates, breaking stories, and current events from trusted sources.',
                        'seo_keywords' => 'latest news, recent updates, breaking news, current news, live news',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'Search',
                        'brief'        => 'Search and find news, articles, topics, categories, locations, and events quickly.',
                        'seo_keywords' => 'search news, find news, news search, article search, topic search',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'Contact',
                        'brief'        => 'Get in touch with us for inquiries, feedback, support, news tips, or business communication.',
                        'seo_keywords' => 'contact us, support, feedback, news tips, communication',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900">Contact Us</h2>
            <p class="mt-3 text-gray-600">
                We would love to hear from you. Reach out to us for support, feedback, news submissions, or business inquiries.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-2xl font-semibold text-gray-900 mb-6">
                    Contact Information
                </h3>

                <div class="space-y-5">
                    <div>
                        <h4 class="font-medium text-gray-500">Email</h4>
                        <p class="text-gray-800">info@example.com</p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-500">Phone</h4>
                        <p class="text-gray-800">+880 1234 567890</p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-500">Address</h4>
                        <p class="text-gray-800">
                            123 Business Street, Dhaka, Bangladesh
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl shadow-sm border border-gray-200">
                <iframe
                    src="https://www.google.com/maps?q=Dhaka,Bangladesh&output=embed"
                    width="100%"
                    height="350"
                    style="border:0;"
                    loading="lazy"
                    allowfullscreen="">
                </iframe>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'About',
                        'brief'        => 'Learn more about our news platform, mission, values, editorial focus, and commitment to reliable journalism.',
                        'seo_keywords' => 'about us, news platform, our mission, journalism, media organization',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">About Us</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                We are committed to delivering reliable, timely, and meaningful news to our readers with honesty, accuracy, and responsibility.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center mb-12">
            <div>
                <h3 class="text-2xl font-semibold text-gray-900 mb-4">Who We Are</h3>
                <p class="text-gray-700 leading-relaxed">
                    Our news platform is built to inform, educate, and connect people with the stories that matter. We cover local, national, and global news with a strong focus on truth, public interest, and responsible journalism.
                </p>
            </div>

            <div class="bg-gray-50 rounded-2xl p-6 sm:p-8 shadow-sm">
                <h3 class="text-2xl font-semibold text-gray-900 mb-4">Our Mission</h3>
                <p class="text-gray-700 leading-relaxed">
                    Our mission is to provide accurate news, thoughtful analysis, and useful information that helps readers understand events and make informed decisions.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">Reliable News</h4>
                <p class="text-gray-600 leading-relaxed">
                    We focus on verified information, credible sources, and clear reporting.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">Editorial Integrity</h4>
                <p class="text-gray-600 leading-relaxed">
                    Our editorial approach is guided by fairness, balance, and public responsibility.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">Community Focus</h4>
                <p class="text-gray-600 leading-relaxed">
                    We value stories that reflect people, communities, culture, and social issues.
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'Public Policy',
                        'brief'        => 'Read our public policy information, community standards, content guidelines, and platform responsibilities.',
                        'seo_keywords' => 'public policy, content policy, community guidelines, platform policy, user safety',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Public Policy</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                Our public policy explains our community standards, content guidelines, user safety principles, and platform responsibilities.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Content Standards</h3>
                <p class="text-gray-600 leading-relaxed">
                    We aim to publish accurate, responsible, and respectful content that serves the public interest.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Community Guidelines</h3>
                <p class="text-gray-600 leading-relaxed">
                    Users are expected to engage respectfully and avoid harmful, abusive, misleading, or unlawful activities.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">User Safety</h3>
                <p class="text-gray-600 leading-relaxed">
                    We prioritize user safety by maintaining responsible moderation, reporting options, and secure platform practices.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Editorial Responsibility</h3>
                <p class="text-gray-600 leading-relaxed">
                    Our editorial process follows fairness, verification, transparency, and accountability.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Platform Responsibility</h3>
                <p class="text-gray-600 leading-relaxed">
                    We work to keep our platform reliable, accessible, secure, and useful for readers and contributors.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Policy Updates</h3>
                <p class="text-gray-600 leading-relaxed">
                    We may update our policies when needed to improve safety, compliance, transparency, and user experience.
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'Terms and Conditions',
                        'brief'        => 'Review the terms, conditions, rules, and responsibilities for using our website and services.',
                        'seo_keywords' => 'terms and conditions, website terms, user agreement, service terms, legal policy',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Terms and Conditions</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                Please read these terms carefully before using our website, content, and services.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Use of Website</h3>
                <p class="text-gray-600 leading-relaxed">
                    By using our website, you agree to follow our rules, policies, and applicable laws.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">User Responsibility</h3>
                <p class="text-gray-600 leading-relaxed">
                    Users must not misuse the website, publish harmful content, or attempt unauthorized access.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Content Ownership</h3>
                <p class="text-gray-600 leading-relaxed">
                    All published content, design, text, images, and materials belong to their respective owners.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Accuracy of Information</h3>
                <p class="text-gray-600 leading-relaxed">
                    We try to provide accurate information, but we do not guarantee that all content is always complete or error-free.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Third-Party Links</h3>
                <p class="text-gray-600 leading-relaxed">
                    Our website may contain external links. We are not responsible for third-party websites or their content.
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Changes to Terms</h3>
                <p class="text-gray-600 leading-relaxed">
                    We may update these terms when needed. Continued use of the website means you accept the updated terms.
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                ]),
            ],

            (object) [
                'language_code' => SeederHelper::LANGUAGE_BN_CODE,
                'pages'         => collect([
                    (object) [
                        'title'        => 'হোম',
                        'brief'        => 'সর্বশেষ খবর, গুরুত্বপূর্ণ সংবাদ, আলোচিত প্রতিবেদন এবং প্রধান শিরোনাম একসাথে দেখুন।',
                        'seo_keywords' => 'হোম, সংবাদ পোর্টাল, সর্বশেষ খবর, ব্রেকিং নিউজ, প্রধান খবর',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'সর্বশেষ',
                        'brief'        => 'সাম্প্রতিক সংবাদ, চলমান ঘটনা, ব্রেকিং নিউজ এবং নতুন প্রকাশিত প্রতিবেদন পড়ুন।',
                        'seo_keywords' => 'সর্বশেষ খবর, সাম্প্রতিক সংবাদ, ব্রেকিং নিউজ, নতুন খবর, চলমান ঘটনা',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'অনুসন্ধান',
                        'brief'        => 'খবর, নিবন্ধ, বিষয়, বিভাগ, স্থান এবং ইভেন্ট দ্রুত খুঁজে বের করুন।',
                        'seo_keywords' => 'সংবাদ অনুসন্ধান, খবর খুঁজুন, নিবন্ধ অনুসন্ধান, বিষয় অনুসন্ধান, সার্চ নিউজ',
                        "body"         => null,
                    ],
                    (object) [
                        'title'        => 'যোগাযোগ',
                        'brief'        => 'জিজ্ঞাসা, মতামত, সহায়তা, সংবাদ তথ্য বা ব্যবসায়িক যোগাযোগের জন্য আমাদের সাথে যোগাযোগ করুন।',
                        'seo_keywords' => 'যোগাযোগ, আমাদের সাথে যোগাযোগ, সহায়তা, মতামত, সংবাদ তথ্য',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900">যোগাযোগ করুন</h2>
            <p class="mt-3 text-gray-600">
                সহায়তা, মতামত, সংবাদ তথ্য প্রদান অথবা ব্যবসায়িক যোগাযোগের জন্য আমাদের সাথে যোগাযোগ করুন।
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-2xl font-semibold text-gray-900 mb-6">
                    যোগাযোগের তথ্য
                </h3>

                <div class="space-y-5">
                    <div>
                        <h4 class="font-medium text-gray-500">ইমেইল</h4>
                        <p class="text-gray-800">info@example.com</p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-500">মোবাইল</h4>
                        <p class="text-gray-800">+880 1234 567890</p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-500">ঠিকানা</h4>
                        <p class="text-gray-800">
                            ১২৩ বিজনেস স্ট্রিট, ঢাকা, বাংলাদেশ
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl shadow-sm border border-gray-200">
                <iframe
                    src="https://www.google.com/maps?q=Dhaka,Bangladesh&output=embed"
                    width="100%"
                    height="350"
                    style="border:0;"
                    loading="lazy"
                    allowfullscreen="">
                </iframe>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'আমাদের সম্পর্কে',
                        'brief'        => 'আমাদের সংবাদ প্ল্যাটফর্ম, লক্ষ্য, মূল্যবোধ, সম্পাদকীয় নীতি এবং নির্ভরযোগ্য সাংবাদিকতা সম্পর্কে জানুন।',
                        'seo_keywords' => 'আমাদের সম্পর্কে, সংবাদ প্ল্যাটফর্ম, আমাদের লক্ষ্য, সাংবাদিকতা, মিডিয়া প্রতিষ্ঠান',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">আমাদের সম্পর্কে</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                আমরা সততা, নির্ভুলতা এবং দায়িত্বশীলতার সাথে পাঠকদের কাছে নির্ভরযোগ্য, সময়োপযোগী ও গুরুত্বপূর্ণ সংবাদ পৌঁছে দিতে প্রতিশ্রুতিবদ্ধ।
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center mb-12">
            <div>
                <h3 class="text-2xl font-semibold text-gray-900 mb-4">আমরা কারা</h3>
                <p class="text-gray-700 leading-relaxed">
                    আমাদের সংবাদ প্ল্যাটফর্ম মানুষের জানা, বোঝা এবং গুরুত্বপূর্ণ ঘটনার সাথে সংযুক্ত থাকার জন্য তৈরি। আমরা স্থানীয়, জাতীয় ও আন্তর্জাতিক সংবাদ নির্ভরযোগ্য তথ্য, জনস্বার্থ এবং দায়িত্বশীল সাংবাদিকতার ভিত্তিতে প্রকাশ করি।
                </p>
            </div>

            <div class="bg-gray-50 rounded-2xl p-6 sm:p-8 shadow-sm">
                <h3 class="text-2xl font-semibold text-gray-900 mb-4">আমাদের লক্ষ্য</h3>
                <p class="text-gray-700 leading-relaxed">
                    আমাদের লক্ষ্য হলো পাঠকদের কাছে সঠিক সংবাদ, বিশ্লেষণ এবং প্রয়োজনীয় তথ্য পৌঁছে দেওয়া, যাতে তারা ঘটনাগুলো ভালোভাবে বুঝতে পারে এবং সচেতন সিদ্ধান্ত নিতে পারে।
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">নির্ভরযোগ্য সংবাদ</h4>
                <p class="text-gray-600 leading-relaxed">
                    আমরা যাচাইকৃত তথ্য, বিশ্বাসযোগ্য উৎস এবং পরিষ্কার প্রতিবেদনের ওপর গুরুত্ব দিই।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">সম্পাদকীয় সততা</h4>
                <p class="text-gray-600 leading-relaxed">
                    আমাদের সম্পাদকীয় নীতি ন্যায্যতা, ভারসাম্য এবং জনদায়িত্বের ভিত্তিতে পরিচালিত।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h4 class="text-xl font-semibold text-gray-900 mb-3">কমিউনিটি ফোকাস</h4>
                <p class="text-gray-600 leading-relaxed">
                    আমরা মানুষ, সমাজ, সংস্কৃতি এবং সামাজিক সমস্যার গল্পগুলোকে গুরুত্ব দিই।
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'গোপনীয়তা নীতি',
                        'brief'        => 'আপনার তথ্য সংগ্রহ, ব্যবহার, সংরক্ষণ এবং সুরক্ষা সম্পর্কিত আমাদের গোপনীয়তা নীতি পড়ুন।',
                        'seo_keywords' => 'গোপনীয়তা নীতি, প্রাইভেসি পলিসি, ব্যক্তিগত তথ্য, তথ্য সুরক্ষা, ডেটা নীতি',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">গোপনীয়তা নীতি</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                আপনার ব্যক্তিগত তথ্য কীভাবে সংগ্রহ, ব্যবহার, সংরক্ষণ এবং সুরক্ষিত করা হয় তা আমাদের গোপনীয়তা নীতিতে ব্যাখ্যা করা হয়েছে।
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তথ্য সংগ্রহ</h3>
                <p class="text-gray-600 leading-relaxed">
                    আমরা সেবা প্রদান, যোগাযোগ এবং ব্যবহারকারীর অভিজ্ঞতা উন্নত করার জন্য প্রয়োজনীয় তথ্য সংগ্রহ করতে পারি।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তথ্যের ব্যবহার</h3>
                <p class="text-gray-600 leading-relaxed">
                    সংগৃহীত তথ্য সেবা পরিচালনা, সহায়তা প্রদান, নিরাপত্তা নিশ্চিতকরণ এবং কনটেন্ট উন্নয়নের জন্য ব্যবহার করা হয়।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তথ্য সংরক্ষণ</h3>
                <p class="text-gray-600 leading-relaxed">
                    আপনার তথ্য প্রয়োজনীয় সময় পর্যন্ত নিরাপদভাবে সংরক্ষণ করা হয় এবং অননুমোদিত প্রবেশ থেকে সুরক্ষিত রাখা হয়।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তথ্য সুরক্ষা</h3>
                <p class="text-gray-600 leading-relaxed">
                    আমরা ব্যবহারকারীর তথ্য সুরক্ষার জন্য যুক্তিসঙ্গত নিরাপত্তা ব্যবস্থা গ্রহণ করি।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">কুকিজ</h3>
                <p class="text-gray-600 leading-relaxed">
                    ওয়েবসাইটের কার্যকারিতা, বিশ্লেষণ এবং ব্যবহারকারীর অভিজ্ঞতা উন্নত করার জন্য কুকিজ ব্যবহার করা হতে পারে।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">নীতির পরিবর্তন</h3>
                <p class="text-gray-600 leading-relaxed">
                    প্রয়োজন অনুযায়ী আমরা এই গোপনীয়তা নীতি পরিবর্তন করতে পারি এবং হালনাগাদ নীতি ওয়েবসাইটে প্রকাশ করা হবে।
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                    (object) [
                        'title'        => 'শর্তাবলী',
                        'brief'        => 'আমাদের ওয়েবসাইট ও সেবা ব্যবহারের নিয়ম, শর্ত, দায়িত্ব এবং নীতিমালা পড়ুন।',
                        'seo_keywords' => 'শর্তাবলী, ব্যবহারের শর্ত, ওয়েবসাইট নীতি, ব্যবহারকারী চুক্তি, আইনি নীতি',
                        'body'         => '
<section class="bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">শর্তাবলী</h2>
            <p class="mt-4 text-gray-600 max-w-3xl mx-auto">
                আমাদের ওয়েবসাইট, কনটেন্ট এবং সেবা ব্যবহারের আগে অনুগ্রহ করে এই শর্তাবলী ভালোভাবে পড়ুন।
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">ওয়েবসাইট ব্যবহার</h3>
                <p class="text-gray-600 leading-relaxed">
                    আমাদের ওয়েবসাইট ব্যবহার করলে আপনি আমাদের নিয়ম, নীতি এবং প্রযোজ্য আইন মেনে চলতে সম্মত হন।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">ব্যবহারকারীর দায়িত্ব</h3>
                <p class="text-gray-600 leading-relaxed">
                    ব্যবহারকারীরা ওয়েবসাইটের অপব্যবহার, ক্ষতিকর কনটেন্ট প্রকাশ বা অননুমোদিত প্রবেশের চেষ্টা করতে পারবেন না।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">কনটেন্টের মালিকানা</h3>
                <p class="text-gray-600 leading-relaxed">
                    প্রকাশিত সকল কনটেন্ট, ডিজাইন, লেখা, ছবি এবং উপকরণ তাদের নিজ নিজ মালিকের অধিকারভুক্ত।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তথ্যের নির্ভুলতা</h3>
                <p class="text-gray-600 leading-relaxed">
                    আমরা সঠিক তথ্য প্রকাশের চেষ্টা করি, তবে সকল কনটেন্ট সবসময় সম্পূর্ণ বা ভুলমুক্ত হবে এমন নিশ্চয়তা দিই না।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">তৃতীয় পক্ষের লিংক</h3>
                <p class="text-gray-600 leading-relaxed">
                    আমাদের ওয়েবসাইটে বাইরের লিংক থাকতে পারে। তৃতীয় পক্ষের ওয়েবসাইট বা তাদের কনটেন্টের জন্য আমরা দায়ী নই।
                </p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-900 mb-3">শর্ত পরিবর্তন</h3>
                <p class="text-gray-600 leading-relaxed">
                    প্রয়োজন অনুযায়ী আমরা এই শর্তাবলী পরিবর্তন করতে পারি। ওয়েবসাইট ব্যবহার চালিয়ে গেলে আপডেট করা শর্তাবলী গ্রহণ করা হয়েছে বলে গণ্য হবে।
                </p>
            </div>
        </div>
    </div>
</section>',
                    ],
                ]),
            ],
        ]);
    }
}
