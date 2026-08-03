<?php
/**
 * Validator input sederhana, native, tanpa dependency luar.
 *
 * Contoh pemakaian:
 *   $validator = new Validation($_POST);
 *   $validator->required('email', 'Email wajib diisi')
 *             ->email('email', 'Format email tidak valid')
 *             ->minLength('password', 6, 'Password minimal 6 karakter');
 *
 *   if ($validator->fails()) {
 *       set_old_input($_POST);
 *       flash('errors', implode('<br>', $validator->errors()));
 *       redirect('/login');
 *   }
 */
class Validation
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function minLength(string $field, int $length, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (strlen($value) < $length) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $message): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$otherField] ?? null)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function numeric(string $field, string $message): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** Ambil semua pesan error sebagai array flat (untuk ditampilkan sekaligus). */
    public function allMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $msg) {
                $messages[] = $msg;
            }
        }
        return $messages;
    }
}
