<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Payments\PaymentManager;
use App\Services\ImageService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request, SettingsService $settings, PaymentManager $payments): View
    {
        return view('admin.settings', [
            'values' => collect(SettingsService::definitions())->keys()->mapWithKeys(fn ($k) => [$k => $settings->all()[$k] ?? null]),
            'fallbacks' => collect(SettingsService::definitions())->filter()->map(fn ($path) => config($path)),
            'tab' => in_array($request->query('tab'), ['general', 'content', 'delivery', 'payments'], true) ? $request->query('tab') : 'general',
            'gateways' => collect(config('payments.gateways'))->map(fn ($cfg, $code) => [
                'label' => $cfg['label'],
                'enabled' => in_array($code, config('payments.enabled', []), true),
                'available' => $payments->isAvailable($code),
            ]),
        ]);
    }

    public function update(Request $request, SettingsService $settings, ImageService $images): RedirectResponse
    {
        $maxKb = (int) config('shop.uploads.max_kb');
        $data = $request->validate([
            'announcement' => ['nullable', 'string', 'max:160'],
            'hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:300'],
            'hero_image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKb}"],
            'remove_hero_image' => ['nullable', 'boolean'],
            'about_text' => ['nullable', 'string', 'max:400'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:160'],
            'courier_name' => ['nullable', 'string', 'max:120'],
            // Only http(s) links: prevents javascript: URLs behind the courier button.
            'courier_url' => ['nullable', 'url:http,https', 'max:500'],
            'courier_phone' => ['nullable', 'string', 'max:40'],
            'courier_whatsapp' => ['nullable', 'string', 'max:40'],
            'courier_notes' => ['nullable', 'string', 'max:500'],
            'delivery_info' => ['nullable', 'string', 'max:3000'],
            'tab' => ['nullable', 'string'],
        ], [], ['courier_url' => 'URL de la plateforme de livraison', 'contact_email' => 'e-mail de contact']);

        if ($request->hasFile('hero_image_file')) {
            $images->delete(setting('hero_image'));
            $data['hero_image'] = $images->store($request->file('hero_image_file'), 'content');
        } elseif ($request->boolean('remove_hero_image')) {
            $images->delete(setting('hero_image'));
            $data['hero_image'] = null;
        }

        $settings->set(collect($data)->except(['hero_image_file', 'remove_hero_image', 'tab'])->all());

        return redirect()->route('admin.settings.edit', ['tab' => $data['tab'] ?? 'general'])
            ->with('toast', ['type' => 'success', 'message' => 'Paramètres enregistrés.']);
    }
}
