<link rel="stylesheet" href = "/css/login.css" />

<div class="login-page">
    <div class="login">
        <div class="login-content">
            <h1>Log In</h1>

            <div class ="login-image">
                <div class="image-placeholder">
                    Image
                </div>
            </div>
        </div>

         <div class="login-form">
            <?= $this->Flash->render() ?>
            <?= $this->Form->create() ?>
            <fieldset>
                <?= $this->Form->control('username', ['required' => true]) ?>
                <?= $this->Form->control('user_password', ['label' => 'Password', 'type' => 'password', 'required' => true]) ?>
            </fieldset>
            <?= $this->Form->submit(__('Login')); ?>
            <?= $this->Form->end()?>
        </div>
        
    </div>

   

</div>