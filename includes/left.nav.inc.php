    <!-- Sidenav (lg) -->
    <aside class="aside">
        <nav class="navbar navbar-expand-xl navbar-vertical">
            <div class="container-fluid">
                    <!-- Brand -->
                    <a class="navbar-brand fs-5 fw-bold px-xl-3 mb-xl-4" href="<?= PROOT; ?>">
                        <i class="fs-4 text-secondary me-1" data-duoicon="box-2"></i> JSpence
                    </a>
                
                    <!-- User -->
                    <div class="ms-auto d-xl-none">
                        <div class="dropdown my-n2">
                            <a class="btn btn-link d-inline-flex align-items-center dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="avatar avatar-sm avatar-status avatar-status-success me-3">
                                <img class="avatar-img" src="<?= PROOT . (($admin_data["admin_profile"] != '') ? $admin_data["admin_profile"] : 'assets/media/avatar.png'); ?>" alt="..." />
                                </span>
                                <?= ucwords($admin_data['admin_fullname']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= PROOT; ?>acc/profile">Account</a></li>
                                <li><a class="dropdown-item" href="<?= PROOT; ?>acc/change-password">Change password</a></li>
                                <li>
                                <hr class="dropdown-divider" />
                                </li>
                                <li><a class="dropdown-item" href="<?= PROOT; ?>auth/logout">Sign out</a></li>
                            </ul>
                        </div>
                    </div>
                
                    <!-- Toggler -->
                    <button
                        class="navbar-toggler ms-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#sidenavLargeCollapse"
                        aria-controls="sidenavLargeCollapse"
                        aria-expanded="false"
                        aria-label="Toggle navigation"
                    >
                        <span class="navbar-toggler-icon"></span>
                    </button>
                
                    <!-- Collapse -->
                    <div class="collapse navbar-collapse" id="sidenavLargeCollapse">
                        <!-- Search -->
                        <div class="input-group d-xl-none my-4 my-xl-0">
                        <input
                            class="form-control"
                            id="topnavSearchInputMobile"
                            type="search"
                            placeholder="Trade"
                            aria-label="Search"
                            aria-describedby="navbarSearchMobile" 
                            readonly style="cursor: pointer" data-bs-target="<?= ((!admin_has_permission()) ? '#buyModal' : ''); ?>" data-bs-toggle="modal"
                        />
                        <span class="input-group-text" id="navbarSearchMobile">
                            <span class="material-symbols-outlined"> send_money </span>
                        </span>
                    </div>
                
                    <!-- Nav -->
                    <nav class="navbar-nav nav-pills mb-7">
                        <div class="nav-item">
                            <a
                            class="nav-link active"
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#dashboards"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="dashboards"
                            >
                            <span class="material-symbols-outlined me-3">space_dashboard</span> Dashboards
                            </a>
                            <div class="collapse show" id="dashboards">
                                <nav class="nav nav-pills">
                                    <a class="nav-link active" href="<?= PROOT; ?>index"><?= _admin_position($admin_data['admin_id']); ?></a>
                                    <a class="nav-link" href="<?= PROOT; ?>analytics">Analytics</a>
                                </nav>
                            </div>
                        </div>
                        <div class="nav-item">
                            <a
                            class="nav-link "
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#orders"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="orders"
                            >
                            <span class="material-symbols-outlined me-3">storefront</span> Trades
                            </a>
                            <div class="collapse " id="orders">
                            <nav class="nav nav-pills">
                                    <a class="nav-link " href="<?= PROOT; ?>account/trades">Sales</a>
                                    <a class="nav-link " href="<?= PROOT; ?>account/expenditure">Expenditures</a>
                                    <a class="nav-link " href="javascript:;" data-bs-target="<?= ((!admin_has_permission()) ? '#buyModal' : ''); ?>" data-bs-toggle="modal">New trade</a>
                                </nav>
                            </div>
                        </div>
                        <div class="nav-item">
                            <a
                            class="nav-link "
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#projects"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="projects"
                            >
                                <span class="material-symbols-outlined me-3">payments</span> Expenditure
                            </a>
                            <div class="collapse " id="projects">
                                <nav class="nav nav-pills">
                                    <a class="nav-link " href="<?= PROOT; ?>account/expenditure">Expenditures</a>
                                    <a class="nav-link " href="<?= PROOT; ?>account/expenditure?add=1">New expenditure</a>
                                </nav>
                            </div>
                        </div>
                        <div class="nav-item">
                            <a
                            class="nav-link "
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#customers"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="customers"
                            >
                            <span class="material-symbols-outlined me-3">group</span> Admins
                            </a>
                            <div class="collapse " id="customers">
                            <nav class="nav nav-pills">
                                <a class="nav-link " href="<?= PROOT; ?>account/admins">Admins</a>
                                <a class="nav-link " href="./customer.html">Admin details</a>
                                <a class="nav-link " href="<?= PROOT; ?>account/admins?add=1">New admin</a>
                            </nav>
                            </div>
                        </div>
                        <div class="nav-item">
                            <a
                            class="nav-link "
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#account"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="account"
                            >
                                <span class="material-symbols-outlined me-3">person</span> Account
                            </a>
                            <div class="collapse " id="account">
                                <nav class="nav nav-pills">
                                    <a class="nav-link " href="<?= PROOT; ?>account/profile">Account overview</a>
                                    <a class="nav-link " href="<?= PROOT; ?>account/settings">Account settings</a>
                                </nav>
                            </div>
                        </div>
                        <div class="nav-item">
                            <a
                            class="nav-link"
                            href="#"
                            data-bs-toggle="collapse"
                            data-bs-target="#authentication"
                            rol="button"
                            aria-expanded="false"
                            aria-controls="authentication"
                            >
                            <span class="material-symbols-outlined me-3">list_alt</span> Logs
                            </a>
                            <div class="collapse" id="authentication">
                                <nav class="nav nav-pills">
                                    <a class="nav-link" href="<?= PROOT; ?>account/logs">Logs</a>
                                </nav>
                            </div>
                        </div>
                        </nav>
                
                        <!-- Heading -->
                        <h3 class="fs-base px-3 mb-4">Documentation</h3>
                
                        <!-- Nav -->
                        <nav class="navbar-nav mb-xl-7">
                            <div class="nav-item">
                                <a class="nav-link " href="javascript:;">
                                    <span class="material-symbols-outlined me-3">sticky_note_2</span> Getting started
                                    <span class="badge text-bg-primary ms-auto">v1.0.0</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a class="nav-link " href="<?= PROOT; ?>account/profile">
                                    <span class="material-symbols-outlined me-3">deployed_code</span> Account
                                </a>
                            </div>
                            <div class="nav-item">
                                <a class="nav-link " href="<?= PROOT; ?>auth/logout">
                                    <span class="material-symbols-outlined me-3">login</span> Logout
                                </a>
                            </div>
                        </nav>
                
                        <!-- Divider -->
                        <hr class="my-4 d-xl-none" />
                
                        <!-- Nav -->
                        <nav class="navbar-nav d-xl-none mb-7">
                        <div class="nav-item">
                            <a class="nav-link" href="#"> <span class="material-symbols-outlined me-3">contrast</span> Dark mode </a>
                        </div>
                        <div class="nav-item">
                            <a class="nav-link" href="#"> <span class="material-symbols-outlined me-3">local_mall</span> Trade </a>
                        </div>
                        <div class="nav-item">
                            <a class="nav-link" href="#"> <span class="material-symbols-outlined me-3">alternate_email</span> Contact us </a>
                        </div>
                        </nav>
                
                        <!-- Card -->
                        <div class="card mt-auto">
                        <div class="card-body">
                            <!-- Heading -->
                            <h6>Need help?</h6>
                
                            <!-- Text -->
                            <p class="text-body-secondary mb-0">Feel free to reach out to us, should you have any questions or suggestions.</p>
                        </div>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>
                    