<?php

namespace App\Listeners;

use App\Events\SuperAdminMenuEvent;

class SuperAdminMenuListener
{

    /**
     * Handle the event.
     */
    public function handle(SuperAdminMenuEvent $event): void
    {
        $module = 'Base';
        $menu = $event->menu;
        $menu->add([
            'title' => __('Dashboard'),
            'icon' => 'home',
            'name' => 'dashboard',
            'parent' => null,
            'order' => 1,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'dashboard',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Users'),
            'icon' => 'user',
            'name' => 'users',
            'parent' => null,
            'order' => 10,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'stores.index',
            'module' => $module,
            'permission' => ''
        ]);

        $menu->add([
            'title' => __('Plan'),
            'icon' => 'trophy',
            'name' => 'plan',
            'parent' => null,
            'order' => 60,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'plan.index',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Plan Request'),
            'icon' => 'arrow-up-right-circle',
            'name' => 'planrequest',
            'parent' => null,
            'order' => 60,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'plan-request.index',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Coupons'),
            'icon' => 'gift',
            'name' => 'coupon',
            'parent' => null,
            'order' => 15,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'plan-coupon.index',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Email Templates'),
            'icon' => 'mail',
            'name' => 'email-templates',
            'parent' => null,
            'order' => 65,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'email_template',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Settings'),
            'icon' => 'settings',
            'name' => 'settings',
            'parent' => null,
            'order' => 100,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'setting.index',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Country'),
            'icon' => 'current-location',
            'name' => 'country',
            'parent' => null,
            'order' => 90,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'country.index',
            'module' => $module,
            'permission' => ''
        ]);
        $menu->add([
            'title' => __('Landing Page'),
            'icon' => 'license',
            'name' => 'landingpage',
            'parent' => null,
            'order' => 90,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'landingpage.index',
            'module' => $module,
            'permission' => ''
        ]);

        $menu->add([
            'title' => __('Add-on Theme'),
            'icon' => 'layout-2',
            'name' => 'add-on-theme',
            'parent' => null,
            'order' => 110,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'addon.index',
            'module' => $module,
            'permission' => ''
        ]);

        $menu->add([
            'title' => __('Add-on Apps'),
            'icon' => 'layout-2',
            'name' => 'add-on-apps',
            'parent' => null,
            'order' => 111,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'addon.apps',
            'module' => $module,
            'permission' => ''
        ]);

    }
}
