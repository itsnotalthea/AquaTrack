<x-app-layout>
    <x-slot name="title">Profile | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">Profile</h1>

        <div class="row g-4">
            <div class="col-lg-6">@include('profile.partials.update-profile-information-form')</div>
            <div class="col-lg-6">
                <div class="d-flex flex-column gap-4">
                    @include('profile.partials.update-password-form')
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
