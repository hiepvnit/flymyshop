<?php

namespace App\Http\Controllers;
use App\Http\Models\Category;
use App\Http\Models\Product;
use Illuminate\Http\Request;
use \League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use \League\OAuth2\Client\Provider\GenericProvider;
use App\Http\Requests, View, Input, Cart;


class StoreController extends Controller
{

    public function address()
    {
        return View::make('account.address');
    }

    public function home()
    {

        $products=Product::featured();
        return view('pages/home',compact('products'));

    }

    public function addAddress()
    {

        $url = env('API_AUTH_URL') . "authorize?client_id=" . env('CLIENT_ID') . "&redirect_uri=" . env('CLIENT_RETURN_URL') . "&response_type=code";
        return View::make('account.addressadd')->with('url', $url);
    }

    public function updateAddress()
    {

        $provider = new GenericProvider([
            'clientId' => env('CLIENT_ID'),
            'clientSecret' => env('CLIENT_SECRET'),
            'redirectUri' => 'http://localhost:8084/update_address',
            'urlAuthorize' => env('API_AUTH_URL') . "authorize",
            'urlAccessToken' => env('API_AUTH_URL') . "access_token",
            'urlResourceOwnerDetails' => env('API_URL') . "api/merchant/data"

        ]);

        if (!isset($_GET['code'])) {
            $authorizationUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authorizationUrl);
            exit;

            // add csrf state check

        } else {

            try {
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => Input::get('code')
                ]);

                echo $accessToken->getToken() . "\n";

                echo $accessToken->getExpires() . "\n";

                // save to database

                $resourceOwner = $provider->getResourceOwner($accessToken);
                var_export($resourceOwner->toArray()); // test

            } catch (IdentityProviderException $e) {
                exit($e->getMessage());
            }

        }

    }


    public function refreshToken()
    {

        $provider = new GenericProvider([
            'clientId' => env('CLIENT_ID'),
            'clientSecret' => env('CLIENT_SECRET'),
            'redirectUri' => 'http://localhost:8084/update_address',
            'urlAuthorize' => env('API_AUTH_URL') . "authorize",
            'urlAccessToken' => env('API_AUTH_URL') . "access_token",
            'urlResourceOwnerDetails' => env('API_URL') . "api/merchant/data"

        ]);


        $currentAccessToken = ""; //get from database

        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $currentAccessToken->getRefreshToken()
        ]);


    }


    public function authUserDetails()
    {

    }

    public function productDetails( $slug)
    {
        $product_id= Product::GetID($slug)->get()->toArray();
        $product=Product::findorFail($product_id[0]['id']);
        return View::make('shop.product',compact('product'));

    }

    public function category($slug)
    {

        $category_id= Category::GetID($slug)->get()->toArray();

        $products="";
        if(count($category_id)>0){
            $category_id= $category_id[0]['id'];
            $products=Product::Category($category_id)->paginate(12);
        }

        return view('shop/products',compact('products'));



    }

    public function cart()
    {


        //  Cart::add('293ad', 'Product 1', 1, 9.99);
        return Cart::content();

    }

}