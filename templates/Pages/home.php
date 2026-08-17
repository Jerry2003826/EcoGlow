<link rel="stylesheet" href="/css/landingPage/home.css" />
 
<div class="landing-page">

    <div class="hero">

        <div class="hero-content">
            <h1>All things Lighting.</h1>

            <p>
                Eco Glow Lighting offers modern lighting fixtures
                and smart home solutions designed to make your
                home feel brighter and better.
                Enquire for our installation and repair services.
            </p>


            <div class="hero-buttons">
                <?= $this->Html->link('Shop all Lighting', '#',
                    ['class' => 'btn btn-primary']
                ) ?>

                <?= $this->Html->link('Make an Enquiry', '/enquiries/add',
                    ['class' => 'btn btn-secondary']
                ) ?>
            </div>
        </div>

        <div class="hero-image">
            <div class="image-placeholder">
                Hero Image
            </div>
        </div>

    </div>

</div>