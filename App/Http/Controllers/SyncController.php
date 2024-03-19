<?php

namespace Modules\TomatoSaaS\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\TomatoSaaS\App\Models\CentralUser;
use Modules\TomatoSaaS\App\Models\Sync;
use Modules\TomatoSaaS\App\Models\Tenant;
use ProtoneMedia\Splade\Facades\Toast;
use Stancl\Tenancy\Features\UserImpersonation;
use TomatoPHP\TomatoAdmin\Facade\Tomato;
use Modules\TomatoSettings\App\Settings\SitesSettings;

class SyncController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return Tomato::index(
            request: $request,
            view: 'tomato-saas::syncs.index',
            model: Sync::class,
            table: \Modules\TomatoSaaS\App\Tables\SyncTable::class,
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function api(Request $request): JsonResponse
    {
        return Tomato::json(
            request: $request,
            model: \Modules\TomatoSaaS\App\Models\Sync::class,
        );
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return Tomato::create(
            view: 'tomato-saas::syncs.create',
        );
    }

    /**
     * @param \Modules\TomatoSaaS\App\Http\Requests\Sync\SyncStoreRequest $request
     * @return RedirectResponse
     */
    public function store(\Modules\TomatoSaaS\App\Http\Requests\Sync\SyncStoreRequest $request): RedirectResponse
    {
        $request->validated();

        $sync = new CentralUser();
        $sync->global_id = $request->get('username');
        $sync->first_name = $request->get('first_name');
        $sync->last_name = $request->get('last_name');
        $sync->password = bcrypt($request->get('password'));
        $sync->email = $request->get('email');
        $sync->phone = $request->get('phone');
        $sync->type = $request->get('type');
        $sync->plan = $request->get('plan');
        $sync->user_id = auth('web')->user()->id;
        $sync->username = Str::lower($request->get('username'));
        $sync->store = $request->get('store');
        $sync->apps = [];
        $sync->save();

        $saas = Tenant::create([
            'id' => $request->get('username')
        ]);

        $saas->domains()->create([
            'domain' => $request->get('username') .'.'. config('tenancy.central_domains.0')
        ]);

        $sync->tenants()->attach($saas);
        $token = tenancy()->impersonate($saas, 1, '/admin', 'web');

        Toast::title(__('Your Domain Has Been Created'))->success()->autoDismiss(5);
        return redirect()->to('https://'.$saas->domains[0]->domain . '/admin/login/url?token='.$token->token .'&email='. $sync->email);
    }
    /**
     * @param \Modules\TomatoSaaS\App\Models\Sync $model
     * @return View
     */
    public function show(\Modules\TomatoSaaS\App\Models\Sync $model): View
    {
        return Tomato::get(
            model: $model,
            view: 'tomato-saas::syncs.show',
        );
    }

    /**
     * @param \Modules\TomatoSaaS\App\Models\Sync $model
     * @return View
     */
    public function edit(\Modules\TomatoSaaS\App\Models\Sync $model): View
    {
        return Tomato::get(
            model: $model,
            view: 'tomato-saas::syncs.edit',
        );
    }

    /**
     * @param \Modules\TomatoSaaS\App\Http\Requests\Sync\SyncUpdateRequest $request
     * @param \Modules\TomatoSaaS\App\Models\Sync $user
     * @return RedirectResponse
     */
    public function update(\Modules\TomatoSaaS\App\Http\Requests\Sync\SyncUpdateRequest $request, \Modules\TomatoSaaS\App\Models\CentralUser $model): RedirectResponse
    {
        $request->validated();

        $model->first_name = $request->get('first_name');
        $model->last_name = $request->get('last_name');
        if($request->get('password') && !empty($request->get('password'))){
            $model->password = bcrypt($request->get('password'));
        }
        $model->email = $request->get('email');
        $model->phone = $request->get('phone');
        $model->store = $request->get('store');
        $model->type = $request->get('type');
        $model->plan = $request->get('plan');
        $model->apps = [];
        $model->save();

        Toast::title(__('Sync updated successfully'))->success()->autoDismiss(2);
        return redirect()->route( 'admin.syncs.index');
    }

    /**
     * @param \Modules\TomatoSaaS\App\Models\CentralUser $model
     * @return RedirectResponse
     */
    public function destroy(\Modules\TomatoSaaS\App\Models\CentralUser $model): RedirectResponse
    {
        Tenant::find($model->username)?->delete();
        $model->tenants()->sync([]);
        $response =  Tomato::destroy(
            model: $model,
            message: __('Sync deleted successfully'),
            redirect: 'admin.syncs.index',
        );

        return $response->redirect;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function url(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => "required|string",
            'email' => "required|string|email|max:255",
        ]);

        $sync = Sync::where('email', $request->get('email'))->first();
        if($sync){
            $admin = User::find(1);
            $admin->name = $sync->first_name . ' ' . $sync->last_name;
            $admin->password = $sync->password;
            $admin->email = $sync->email;
            $admin->save();

            $admin->roles()->sync([1]);

            if($sync->store){
                $site = new SitesSettings();
                $site->site_name = $sync->store;
                $site->save();
            }
        }

        return UserImpersonation::makeResponse($request->get('token'));
    }

    /**
     * @param CentralUser $model
     * @return RedirectResponse
     */
    public function impersonate(\Modules\TomatoSaaS\App\Models\CentralUser $model): RedirectResponse
    {
        $saas = Tenant::where('id', $model->username)->first();
        $token = tenancy()->impersonate($saas, 1, '/admin');
        return redirect()->to('https://'.$saas->domains[0]->domain . '/admin/login/url?token='.$token->token .'&email='. $model->email);
    }
}
