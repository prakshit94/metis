<?php
// Included by users_directory.php with its isolated users, permissions and HTTP helper.
app('session')->driver()->invalidate();
[$status] = directoryHttp('PATCH', '/api/users/'.$actor->id, $actorToken, ['two_factor_required'=>true,'security_current_password'=>'Example123!']);
check($status===403 && !$actor->fresh()->two_factor_required, 'user-edit alone cannot change two-step policy');
[$status] = directoryHttp('PATCH', '/api/users/'.$other->id, $rootToken, ['two_factor_required'=>true]);
check($status===422 && !$other->fresh()->two_factor_required, 'admin policy change requires current password');
$staleToken=$other->createToken('before-required')->plainTextToken;
[$status] = directoryHttp('PATCH', '/api/users/'.$other->id, $rootToken, ['two_factor_required'=>true,'security_current_password'=>'Example123!']);
check($status===200 && $other->fresh()->two_factor_required && $other->tokens()->count()===0, 'requiring two-step login revokes existing target credentials');
check(!app(\App\Services\TwoFactorService::class)->enabled($other), 'admin requirement does not fabricate owner enrollment');
[$status] = directoryHttp('PATCH', '/api/users/'.$other->id, $rootToken, ['two_factor_required'=>false,'security_current_password'=>'Example123!']);
check($status===200 && !$other->fresh()->two_factor_required, 'verified admin can remove a per-user requirement');
[$status,$created] = directoryHttp('POST','/api/users',$rootToken,['name'=>'Required creation','first_name'=>'Required','last_name'=>'Creation','email'=>'required-created@example.invalid','password'=>'Example123!','password_confirmation'=>'Example123!','two_factor_required'=>true,'security_current_password'=>'Example123!']);
check($status===201 && \App\Modules\Users\Models\User::where('email','required-created@example.invalid')->value('two_factor_required'), 'create user supports verified admin requirement without enrolling a factor');
