<?php
/**
 * SmoothPHP
 * This file is part of the SmoothPHP project.
 * **********
 * Copyright © 2015-2020
 * License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
 * **********
 * RegisterController.php
 */

namespace Test\Controllers;

use SmoothPHP\Framework\Core\Abstracts\Controller;
use SmoothPHP\Framework\Core\Kernel;
use SmoothPHP\Framework\Database\Mapper\DBObjectMapper;
use SmoothPHP\Framework\Flow\Requests\Request;
use SmoothPHP\Framework\Forms\Constraints\FileImageConstraint;
use SmoothPHP\Framework\Forms\Constraints\FileImageMaximumSizeConstraint;
use SmoothPHP\Framework\Forms\Constraints\FileImageMinimumSizeConstraint;
use SmoothPHP\Framework\Forms\Constraints\FileSizeConstraint;
use SmoothPHP\Framework\Forms\Constraints\IdenticalConstraint;
use SmoothPHP\Framework\Forms\Form;
use SmoothPHP\Framework\Forms\FormBuilder;
use SmoothPHP\Framework\Forms\Types\EmailType;
use SmoothPHP\Framework\Forms\Types\FileType;
use SmoothPHP\Framework\Forms\Types\PasswordType;
use SmoothPHP\Framework\Forms\Types\SubmitType;
use Test\Model\Constraints\EmailExistsConstraint;
use Test\Model\TestUser;

class RegisterController extends Controller {
	/* @var Form */
	private $registerForm;
	/* @var DBObjectMapper */
	private $userMap;

	public function onInitialize(Kernel $kernel) {
		// Create a registration form
		$registerFormBuilder = new FormBuilder();
		$registerFormBuilder->add('mail', EmailType::class, [
			'constraints' => [
				new EmailExistsConstraint()
			]
		]);

		$registerFormBuilder->add('password', PasswordType::class);
		$registerFormBuilder->add('repeat', PasswordType::class, [
			'label'       => 'Repeat password',
			'constraints' => [
				new IdenticalConstraint('password')
			]
		]);

		$registerFormBuilder->add('avatar', FileType::class, [
			'required'    => false,
			'constraints' => [
				new FileImageConstraint(),
				new FileImageMinimumSizeConstraint(16, 16),
				new FileImageMaximumSizeConstraint(64, 64),
				new FileSizeConstraint(64 * 1024) // 64KB
			]
		]);

		$registerFormBuilder->add('submit', SubmitType::class);
		$this->registerForm = $registerFormBuilder->getForm();

		// Create a user mapping
		$this->userMap = $kernel->getDatabase()->map(TestUser::class);
	}

	public function register(Request $request) {
		$this->registerForm->validate($request);

		if (!$this->registerForm->hasResult() || !$this->registerForm->isValid())
			return self::render('register/form.tpl', [
				'form' => $this->registerForm
			]);

		$newUser = new TestUser();
		$newUser->email = $request->post->mail;
		$newUser->setPassword($request->post->password); // We use setPassword() to invoke our bcrypt hashing

		if ($request->files->avatar && $request->files->avatar->isUploaded()) {
			// Convert the image to png
			$img = imagecreatefromstring(file_get_contents($request->files->avatar->location));
			ob_start();
			imagepng($img, null, 9);

			$newUser->avatar = ob_get_clean();
		}

		// Insert the user into the database
		$this->userMap->insert($newUser);

		return self::render('register/success.tpl');
	}

}