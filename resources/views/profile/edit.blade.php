<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <p class="placard text-[0.6rem]">Cont</p>
            <h2 class="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">
                Profil
            </h2>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden border border-hairline bg-bench">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-[#59e3ff]/45" aria-hidden="true"></div>
                <div class="pointer-events-none absolute left-0 top-0 h-full w-px bg-[#59e3ff]/35" aria-hidden="true"></div>

                <div class="border-b border-hairline px-5 py-5 sm:px-8 sm:py-6">
                    <p class="placard text-[0.6rem]">Setări cont</p>
                    <p class="mt-2 max-w-2xl text-sm text-dim">Actualizează datele de acces și informațiile personale.</p>
                </div>

                <div class="divide-y divide-[#1c242a]">
                    <div class="px-5 py-7 sm:px-8 sm:py-9">
                        <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <div class="px-5 py-7 sm:px-8 sm:py-9">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="bg-[#06080a]/45 px-5 py-7 sm:px-8 sm:py-9">
                        <div class="max-w-2xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
