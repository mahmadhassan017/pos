<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isAdmin = false;

    public function save(): void
    {
        $wasEditing = (bool) $this->editingId;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$wasEditing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'isAdmin' => ['boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => (bool) $validated['isAdmin'],
            'email_verified_at' => now(),
        ];

        if ($validated['password'] ?? false) {
            $data['password'] = $validated['password'];
        }

        User::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->resetPage();

        session()->flash('success', $wasEditing ? 'User updated.' : 'User added.');
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->isAdmin = (bool) $user->is_admin;
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own logged-in account.');

            return;
        }

        User::whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('success', 'User deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->isAdmin = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => User::latest()->paginate(15),
        ]);
    }
}
