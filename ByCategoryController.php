<?php

namespace App\Http\Controllers\Frontend;

use App\Core\ServiceRegistration;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Review;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Models\UserServiceProvider;
use App\Models\Computer;
use App\Models\Maintenance;
use App\Models\Beauty;
use App\Models\Makeup;
use App\Models\Translation;
use App\Models\Tuition;
use App\Models\AccommodationHouse;
use App\Models\AccommodationRoom;
use App\Models\FoodDelivery;
use App\Models\RoomCleaning;
use DB;

class ByCategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showIfHasCategoryOnly($category, $subcategory, Request $request)
    {
        $new_services = UserServiceProvider::inRandomOrder()
        ->take(8)
        ->orderBy('created_at', 'ASC')
        ->get();

        $services = UserServiceProvider::all();
        $categories = get_categories();

        $availableLayouts = [
            'grid',
            'list',
        ];

        $sortType = [
            'desc',
            'asc',
        ];

        $selectionLayout = 'grid';
        $selectionSortBy = 'asc';

        if ($request->has('layout')) {

            if (in_array($request->get('layout'), $availableLayouts)) {
                $selectionLayout = $request->get('layout');
            }

        }

        if ($request->has('sortby')) {

            if (in_array($request->get('sortby'), $sortType)) {
                $selectionSortBy = $request->get('sortby');
            }

        }

        if($subcategory != 'null')
        {    
            $category = Category::where('slug', $category)->firstOrFail();
            $subcategoryid = SubCategory::where('slug', $subcategory)->first();
            $items = UserServiceProvider::with('parentCategory', 'parentSubCategory', 'pricingUnit')
            ->where('sub_category_id', $subcategoryid->id)
            ->orderBy('price', $selectionSortBy)
            ->paginate();

        //    dd($items);

            if ($selectionLayout == 'list') {

                return view('pages.service_grid')->with([
                    'items'               => $items,
                    'category'            => $category,
                    'subCategory'         => $subcategory,
                    'services'            => $services,
                    'categories'          => $categories,
                    'new_service' => $new_services,

                ]);
    
            } else if($selectionLayout == 'grid'){

                return view('pages.service_list')->with([
                    'items'               => $items,
                    'category'            => $category,
                    'subCategory'         => $subcategory,
                    'services'            => $services,
                    'categories'          => $categories,
                    'new_service' => $new_services,

                ]);
    
            }

        }else{

            $category = Category::where('slug', $category)->firstOrFail();
            $items = UserServiceProvider::with('parentCategory', 'parentSubCategory', 'pricingUnit')
            ->where('category_id', $category->id)
            ->orderBy('price', $selectionSortBy)
            ->paginate();

            if ($selectionLayout == 'list') {

                return view('pages.service_grid')->with([
                    'items'               => $items,
                    'category'            => $category,
                    'subCategory'         => $subcategory,
                    'services'            => $services,
                    'categories'          => $categories,
                    'new_service'         => $new_services,

                ]);
    
            } else if($selectionLayout == 'grid'){
    
                return view('pages.service_list')->with([
                    'items'               => $items,
                    'category'            => $category,
                    'subCategory'         => $subcategory,
                    'services'            => $services,
                    'categories'          => $categories,
                    'new_service'         => $new_services,

                ]);
    
            }


        }
     
    }

    public function index($category = null, $subcategory, Request $request)
    {

        $resolveSubCategory = $subcategory == 'null' ? null : $subcategory;
        $subCategories = [];

        try {
            $category = Category::where('slug', $category)->firstOrFail();

            if (is_null($resolveSubCategory)) {
                $subcategory = SubCategory::where('category_id', $category->id)->first();
                $subCategories = SubCategory::where('category_id', $category->id)->pluck('id')->all();
            } else {
                $subcategory = SubCategory::where('category_id', $category->id)->first();
                $subCategories = SubCategory::where('category_id', $category->id)->pluck('id')->all();
            }

        } catch (ModelNotFoundException $e) {

            //render 404 here
        }

        $availableLayouts = [
            'grid',
            'list',
        ];

        $sortType = [
            'popular',
            'desc',
            'asc',
        ];

        //set default layout and sortby for each view request
        $selectionLayout = 'grid';
        $selectionSortBy = 'asc';

        if ($request->has('layout')) {

            if (in_array($request->get('layout'), $availableLayouts)) {
                $selectionLayout = $request->get('layout');
            }

        }

        if ($request->has('sortby')) {

            if (in_array($request->get('sortby'), $sortType)) {
                $selectionSortBy = $request->get('sortby');
            }

        }

        $items = UserServiceProvider::where('category_id', $category->id)
            ->whereIn('sub_category_id', $subCategories)
            ->orderBy('price', $selectionSortBy)
            ->paginate();

        if ($selectionLayout == 'list') {

            //resolve layout
            return view('ListViewService.tutor_ListView')->with([
                'items'               => $items,
                'category'            => $category,
                'subCategory'         => $subcategory,
                'coverages'           => get_coverage_locations(),
                'categories_list'     => get_categories(),
                'sub_categories_list' => get_sub_categories($category->name),
            ]);

        } else {

            $services = UserServiceProvider::all();
            $categories = get_categories();

            return view('pages.service_list')->with([
                'items'               => $items,
                'category'            => $category,
                'subCategory'         => $subcategory,
                'coverages'           => get_coverage_locations(),
                'categories_list'     => get_categories(),
                'sub_categories_list' => get_sub_categories($category->name),
                'services'            => $services,
                'categories'          => $categories
            ]);

        }

    }

    public function create()
    {
        //
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @param $id
     */
    public function show($id)
    {
        try {

            $items = UserServiceProvider::with('parentCategory')->findOrFail($id);

            $serviceRegistry = new ServiceRegistration();

            if ($serviceRegistry->registered($items->parentCategory->slug)) {

                $getServiceLocate = app()->make($serviceRegistry->get($items->parentCategory->slug));
                $getRelationships = $getServiceLocate->beginQueryWithRelationshipLoaded();

                $serviceData = $getRelationships->where('id', $items->attributable_id)
                    ->firstOrFail();

            }

            $providerDetail = get_provider_detail($items->user_id);
            $reviewQuery = Review::where('reviewable_type', $items->attributable_type)->where('reviewable_id', $items->attributable_id);

            $reviewsCount = $reviewQuery->count();
            $reviews = $reviewQuery->get();

            return view('pages.tutor_detail')->with([
                'reviews'        => $reviews,
                'reviewsCount'   => $reviewsCount,
                'item'           => $items, 'serviceData' => $serviceData,
                'providerDetail' => $providerDetail,
            ]);

        } catch (ModelNotFoundException $e) {

        }

    }

    /**
     * @param Request $request
     */
    public function showBooking($id, Request $request)
    {
        $items = UserServiceProvider::with('parentCategory', 'parentSubCategory', 'pricingUnit')
            ->where('id', $id)->firstOrFail();

        $category = $items->parentCategory->slug;

        $serviceRegistry = new ServiceRegistration();
        $serviceInstance = null;

        if ($serviceRegistry->registered($category)) {
            $serviceInstance = app()->make($serviceRegistry->get($category));
        }

        $serviceDetails = $serviceInstance->getRelationships($id);
        $getRelationships = $serviceInstance->beginQueryWithRelationshipLoaded();
        $getRelationships->where('id', $items->attributable_id);

        $metaDetails = $getRelationships->firstOrFail();

        if ($category == "academic") {
            return view('booking_service.tutor_book')->with(['item' => $items, 'details' => $serviceDetails, 'metaDetails' => $metaDetails]);
        } 

    }


    public function showBookingById($id, Request $request)
    {
        $services = UserServiceProvider::all();
        $categories = get_categories();

        $items = UserServiceProvider::with('parentCategory', 'parentSubCategory', 'pricingUnit')
            ->where('id', $id)->firstOrFail();

      //      dd($items->parentSubCategory->name);

        if($items->parentSubCategory->name == "Spa Saloon")
        {
            $JenisModule = Beauty::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Tuition/Tutor")
        {
            $JenisModule = Tuition::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Translation")
        {
            $JenisModule = Translation::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "House")
        {
            $JenisModule = AccommodationHouse::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Room")
        {
            $JenisModule = AccommodationRoom::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Room Cleaning")
        {
            $JenisModule = RoomCleaning::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Maintenance")
        {
            $JenisModule = Maintenance::where('service_provider_id', '=', $items->id)->first();
        }

        $category = $items->parentCategory->slug;

        if ($category == "academic") {

            return view('booking_service.tutor_book')->with([
                'items'               => $items,
                'category'            => $category,
                'services'            => $services,
                'categories'          => $categories,
                'jenismodule'         => $JenisModule
            ]);

        }else if($category == "beauty"){

            return view('booking_service.beauty_book')->with([
                'items'               => $items,
                'category'            => $category,
                'services'            => $services,
                'categories'          => $categories,
                'jenismodule'         => $JenisModule

            ]);
        }else if($category == "accommodation"){

            return view('booking_service.accomodation_book')->with([
                'items'               => $items,
                'category'            => $category,
                'services'            => $services,
                'categories'          => $categories,
                'jenismodule'         => $JenisModule

            ]);

        }else if($category == "cleaning"){

            return view('booking_service.cleaning_book')->with([
                'items'               => $items,
                'category'            => $category,
                'services'            => $services,
                'categories'          => $categories,
                'jenismodule'         => $JenisModule

            ]);

        }else if($category == "computer"){

            return view('booking_service.computer_book')->with([
                'items'               => $items,
                'category'            => $category,
                'services'            => $services,
                'categories'          => $categories,
                'jenismodule'         => $JenisModule

            ]);

        }

    }


    /**
     * @param $id
     */
    public function edit($id)
    {
        //
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * @param $id
     */
    public function destroy($id)
    {
        //
    }

    public function show_serviceByid($id)
    {
        $services = UserServiceProvider::all();
        $categories = get_categories();

        $items = UserServiceProvider::with('parentCategory', 'parentSubCategory', 'pricingUnit')
        ->where('id', $id)->firstOrFail();

        //dd($items->parentSubCategory->name);

        if($items->parentSubCategory->name == "Spa Saloon")
        {
            $JenisModule = Beauty::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Make Up")
        {
            $JenisModule = Makeup::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Tuition/Tutor")
        {
            $JenisModule = Tuition::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Translation")
        {
            $JenisModule = Translation::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "House")
        {
            $JenisModule = AccommodationHouse::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Room")
        {
            $JenisModule = AccommodationRoom::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Food Delivery")
        {
            $JenisModule = FoodDelivery::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Room Cleaning")
        {
            $JenisModule = RoomCleaning::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Maintenance")
        {
            $JenisModule = Maintenance::where('service_provider_id', '=', $items->id)->first();
        }
        else if($items->parentSubCategory->name == "Digital Graphic")
        {
            $JenisModule = Computer::where('service_provider_id', '=', $items->id)->first();
        }

      //  dd($JenisModule);
       // dd($beauty);

        $providerDetail = get_provider_detail($items->user_id);
        $reviewQuery = Review::where('reviewable_type', $items->attributable_type)->where('reviewable_id', $items->attributable_id);

        $reviewsCount = $reviewQuery->count();
        $reviews = $reviewQuery->get();

        return view('pages.service_detail')->with([
            'reviews'        => $reviews,
            'reviewsCount'   => $reviewsCount,
            'item'           => $items,
            'providerDetail' => $providerDetail,
            'services'   => $services,
            'categories' => $categories,
            'jenismodule' => $JenisModule
        ]);

    }

}
