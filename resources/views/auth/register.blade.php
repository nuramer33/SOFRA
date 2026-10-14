<x-layouts.auth :title="__('app.auth.register_title')">
    <div class="reveal" style="--i: 1">
        <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ __('app.auth.register_title') }}</h1>
        <p class="mt-3 text-ink-soft">{{ __('app.auth.register_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-9 space-y-5"
          x-data="slugField(@js(old('slug', '')))" @submit="busy = true">
        @csrf

        <div class="reveal" style="--i: 2">
            <x-field name="shop_name" :label="__('app.auth.shop_name')" :placeholder="__('app.auth.shop_name_placeholder')"
                     autocomplete="organization" required autofocus maxlength="80"
                     x-on:input="fromName($event.target.value)" />
        </div>

        {{-- The public link: shown as a URL, always left-to-right. --}}
        <div class="reveal" style="--i: 3">
            <x-field name="slug" :label="__('app.auth.slug')" :hint="__('app.auth.slug_hint')">
                <x-slot:input>
                    <div dir="ltr" @class([
                            'field flex items-center gap-0 p-0 focus-within:border-clay focus-within:bg-white focus-within:ring-4 focus-within:ring-clay/15',
                            '!border-danger' => $errors->has('slug'),
                        ])>
                        <span class="ps-4 text-ink-mute select-none">{{ parse_url(config('app.url'), PHP_URL_HOST) }}/</span>
                        <input id="slug" name="slug" x-model="slug" @input="clean()" required
                               minlength="3" maxlength="40" pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
                               autocapitalize="none" autocorrect="off" spellcheck="false"
                               @if ($errors->has('slug')) aria-invalid="true" @endif
                               aria-describedby="{{ $errors->has('slug') ? 'slug-error' : 'slug-hint' }}"
                               class="min-w-0 flex-1 bg-transparent py-3.5 pe-4 font-medium text-ink outline-none">
                    </div>
                </x-slot:input>
            </x-field>
        </div>

        <div class="reveal" style="--i: 4">
            <x-field name="name" :label="__('app.auth.owner_name')" autocomplete="name" required maxlength="80" />
        </div>

        <div class="reveal" style="--i: 5">
            <x-field name="email" type="email" :label="__('app.auth.email')" dir="ltr"
                     autocomplete="username" inputmode="email" required />
        </div>

        <div class="reveal grid gap-5 sm:grid-cols-2" style="--i: 6">
            <x-password-field :label="__('app.auth.password')" :hint="__('app.auth.password_hint')" autocomplete="new-password" />
            <x-password-field name="password_confirmation" :label="__('app.auth.password_confirmation')" autocomplete="new-password" />
        </div>

        <div class="reveal" style="--i: 7">
            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-1 size-4 shrink-0 accent-clay">
                <span>{!! __('account.agree', [
                    'terms' => '<a href="'.route('legal.terms').'" target="_blank" class="font-medium text-clay underline-offset-4 hover:underline">'.e(__('legal.footer.terms')).'</a>',
                    'privacy' => '<a href="'.route('legal.privacy').'" target="_blank" class="font-medium text-clay underline-offset-4 hover:underline">'.e(__('legal.footer.privacy')).'</a>',
                ]) !!}</span>
            </label>
            @error('terms')<p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="reveal pt-2" style="--i: 8">
            <button type="submit" class="btn btn-primary group w-full" :disabled="busy">
                {{ __('app.auth.register_button') }}
                <x-icon name="arrow" class="flip-rtl size-4 transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
            </button>
        </div>
    </form>

    <p class="reveal mt-8 text-center text-sm text-ink-soft" style="--i: 8">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-medium text-clay underline-offset-4 hover:underline">{{ __('app.auth.sign_in') }}</a>
    </p>
</x-layouts.auth>
