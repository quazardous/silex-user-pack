<?php
return [
    'user' => [
        'register' => [
            'validators' => [
                'password' => [
                    'length' => [
                        'max' => 'The password is too long. It should have {{ limit }} character or less.|The password is too long. It should have {{ limit }} characters or less.',
                        'min' => 'The password is too short. It should have {{ limit }} character or more.|The password is too short. It should have {{ limit }} characters or more.',
                    ],
                ],
                'username' => [
                    'length' => [
                        'max' => 'The username is too long. It should have {{ limit }} character or less.|The username is too long. It should have {{ limit }} characters or less.',
                        'min' => 'The username is too short. It should have {{ limit }} character or more.|The username is too short. It should have {{ limit }} characters or more.',
                    ],
                ],
                'confirm_password' => [
                    'equal_to' => [
                        'password' => 'The passwords are different.'
                     ],
                 ],
                'username' => [
                    'regex' => [
                        'username' => 'The username must contain only letters or digits and begin with a letter.',
                    ],
                ],
                'user' => [
                    'unique_entity' => [
                        'email' => 'This e-mail is already used.',
                        'username' => 'This username is already used.',
                    ],
                ],
            ],
        ],
        'recover_password_confirm' => [
            'validators' => [
                'password' => [
                    'length' => [
                        'max' => 'The password is too long. It should have {{ limit }} character or less.|The password is too long. It should have {{ limit }} characters or less.',
                        'min' => 'The password is too short. It should have {{ limit }} character or more.|The password is too short. It should have {{ limit }} characters or more.',
                    ],
                 ],
                'confirm_password' => [
                    'equal_to' => [
                        'password' => 'The passwords are different.'
                     ],
                 ],
            ],
        ],
    ]
];