<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '既存ユーザーを管理者にするか、新しい管理者アカウントを作成します';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('メールアドレスを入力してください');

        // ユーザーが既に存在するか確認
        $user = User::where('email', $email)->first();

        if ($user) {
            // 既存ユーザーを管理者にする
            if ($user->is_admin) {
                $this->info("このユーザーは既に管理者です。");
                return Command::SUCCESS;
            }

            if ($this->confirm("{$user->name} ({$user->email}) を管理者にしますか？")) {
                $user->update(['is_admin' => true]);
                $this->info("ユーザーを管理者にしました。");
                return Command::SUCCESS;
            }

            $this->info("キャンセルしました。");
            return Command::FAILURE;
        }

        // 新しい管理者アカウントを作成
        $this->info("新しい管理者アカウントを作成します。");

        $name = $this->ask('名前を入力してください');
        $password = $this->secret('パスワードを入力してください（8文字以上）');
        $passwordConfirmation = $this->secret('パスワードを再入力してください');

        // バリデーション
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('パスワードが一致しません。');
            return Command::FAILURE;
        }

        // 管理者アカウントを作成
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("管理者アカウントを作成しました。");
        $this->info("名前: {$admin->name}");
        $this->info("メール: {$admin->email}");

        return Command::SUCCESS;
    }
}
