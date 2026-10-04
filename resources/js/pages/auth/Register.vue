<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Buat Akun StudyHub',
        description: 'Daftar sekarang untuk mulai mengorganisir aktivitas belajarmu.',
    },
});

const form = useForm({
    name: '',
    email: '',
    class: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar Akun - StudyHub" />

    <form @submit.prevent="submit" class="flex flex-col gap-5">
        <div class="space-y-4">
            <!-- Nama Lengkap -->
            <div class="space-y-1.5">
                <Label for="name" class="text-sm font-medium text-slate-700 dark:text-slate-300">Nama Lengkap</Label>
                <Input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    v-focus
                    autocomplete="name"
                    placeholder="Contoh: Rizky Pratama"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium text-slate-700 dark:text-slate-300">Email</Label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="email"
                    placeholder="nama@email.com"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Kelas -->
            <div class="space-y-1.5">
                <Label for="class" class="text-sm font-medium text-slate-700 dark:text-slate-300">Kelas</Label>
                <Input
                    id="class"
                    type="text"
                    v-model="form.class"
                    required
                    placeholder="Contoh: XII RPL 1 / XI IPA 3"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.class" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <Label for="password" class="text-sm font-medium text-slate-700 dark:text-slate-300">Password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Konfirmasi Password -->
            <div class="space-y-1.5">
                <Label for="password_confirmation" class="text-sm font-medium text-slate-700 dark:text-slate-300">Konfirmasi Password</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                    class="h-10 border-slate-200 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-100 focus:border-indigo-600 focus:ring-indigo-600"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>
        </div>

        <Button
            type="submit"
            class="mt-2 h-11 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all"
            :disabled="form.processing"
        >
            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
            <span>{{ form.processing ? 'Mendaftarkan...' : 'Buat Akun' }}</span>
        </Button>

        <div class="text-center text-sm text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
            Sudah punya akun?
            <Link
                :href="login()"
                class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline ml-1"
            >
                Masuk
            </Link>
        </div>
    </form>
</template>
