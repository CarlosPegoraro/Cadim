<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $avatarIcon = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $profileMessage = '';

    public mixed $admin = null;

    protected function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está sendo usado.',
            'avatarIcon.max' => 'Escolha um ícone válido.',
        ];
    }

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->avatarIcon = auth()->user()->avatar_icon ?? '';
    }

    public function saveProfile(): void
    {
        $this->profileMessage = '';
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'avatarIcon' => ['nullable', 'string', 'max:8'],
            'admin' => ['nullable', 'boolean', Rule::notIn([true, 1, '1'])],
        ]);

        auth()->user()->update(['name' => $data['name'], 'email' => $data['email'], 'avatar_icon' => $data['avatarIcon'] ?: null]);
        $this->profileMessage = 'Dados pessoais salvos com sucesso.';
        $this->dispatch('profile-saved');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required'],
            'password' => ['required', 'min:8', 'same:passwordConfirmation'],
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'A senha atual está incorreta.');

            return;
        }

        auth()->user()->update(['password' => Hash::make($this->password)]);
        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->dispatch('password-saved');
    }

    public function render()
    {
        return view('livewire.profile-settings')->layout('layouts.app');
    }
}
