<?php

/**
 * @file
 * Default theme implementation to display a single Drupal page.
 *
 * @see template_preprocess()
 * @see template_preprocess_page()
 * @see template_process()
 * @see html.tpl.php
 */
?>
<header id="header" class="header" role="header">
    <div>
        <div class="contacts container">
            <div class="email">
                <?php $topPageEmail = check_plain(theme_get_setting('header_email', 'uworld')); ?>
                <span>Email<small>: </small><span><?php print $topPageEmail; ?></span></span>
            </div>
            <div class="phone">
                <?php $topPagePhone = check_plain(theme_get_setting('header_phone', 'uworld')); ?>
                <span>Phone<small>: </small><span><?php print $topPagePhone; ?></span></span>
            </div>
        </div>
        <nav class="navbar navbar-default" role="navigation">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse">
                    <span class="sr-only"><?php print t('Toggle navigation'); ?></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <?php if ($site_name || $logo): ?>
                    <!--          <a href="--><?php //print $front_page; ?><!--" class="navbar-brand" rel="home" title="--><?php //print t('Home'); ?><!--">-->
                    <?php if ($logo): ?>
                        <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" id="logo"/>
                    <?php endif; ?>
                    <!--            --><?php //if ($site_name): ?>
                    <!--              <span class="site-name">--><?php //print $site_name; ?><!--</span>-->
                    <!--            --><?php //endif; ?>
                    <!--          </a>-->
                <?php endif; ?>
            </div> <!-- /.navbar-header -->

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="navbar-collapse">
                <?php if ($main_menu): ?>
                    <ul id="main-menu" class="menu nav navbar-nav">
                        <?php print render($main_menu); ?>
                    </ul>
                <?php endif; ?>
            </div><!-- /.navbar-collapse -->
        </nav><!-- /.navbar -->
    </div> <!-- /.container -->
</header>

<div id="main-wrapper">
    <div id="main" class="main">
        <div class="container">
            <?php if ($breadcrumb): ?>
                <div id="breadcrumb" class="visible-desktop">
                    <?php print $breadcrumb; ?>
                </div>
            <?php endif; ?>
            <?php if ($messages): ?>
                <div id="messages">
                    <?php print $messages; ?>
                </div>
            <?php endif; ?>
            <div id="page-header">
                <?php if ($title): ?>
                    <div class="page-header">
                        <h1 class="title"><?php print $title; ?></h1>
                    </div>
                <?php endif; ?>
                <?php if ($tabs): ?>
                    <div class="tabs">
                        <?php print render($tabs); ?>
                    </div>
                <?php endif; ?>
                <?php if ($action_links): ?>
                    <ul class="action-links">
                        <?php print render($action_links); ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div id="content">
            <?php print render($page['content']); ?>
        </div>
    </div> <!-- /#main -->
</div> <!-- /#main-wrapper -->

<footer id="footer" class="footer" role="footer">
    <div class="container">
        <div class="newsletter">
            <span>Newsletter</span>
            <span>Enter your email address to subscribe our notification of our new post & features by email.</span>
            <form action="#">
                <input type="email" placeholder="EMAIL ADDRESS">
                <button>Subscribe</button>
            </form>
        </div>
        <div class="navigation">
            <?php if ($main_menu): ?>
                <ul class="menu navigation">
                    <h3 class="list-headline">navigation</h3>
                    <?php print render($main_menu); ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ($page['menu_new']): ?>
            <ul class="follow-us">
                <?php print render($page['menu_new']); ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="container">
        <?php if ($copyright): ?>
            <small class="copyright pull-left"><?php print $copyright; ?></small>
        <?php endif; ?>
    </div>
</footer>

