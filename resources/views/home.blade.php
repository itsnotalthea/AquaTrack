<x-app-layout>
    <x-slot name="title">AquaTrack | AquaTrack Water Station</x-slot>

    <header class="hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h1>Know where every gallon and container is.</h1>
                    <p class="lead my-4">AquaTrack keeps orders, payments, deliveries, stock, and reusable containers for AquaTrack Water Station in Marikina City in one place.</p>
                    <a href="{{ route('services') }}#order" class="btn btn-aqua btn-lg me-2">Order water</a>
                    <a href="{{ route('about') }}" class="btn btn-light btn-lg rounded-0 fw-bold">How it works</a>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="{{ asset('assets/logo.png') }}" alt="AquaTrack smiling water drop logo" class="bg-white p-3">
                </div>
            </div>
        </div>
    </header>

    <section class="section">
        <div class="container">
            <h2 class="mb-4">What you can do</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="px-card">
                        <div class="ico">🛒</div>
                        <h4 class="h5 mt-2">Customers</h4>
                        <p>Register, order, choose pickup or delivery, and follow your order status.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="px-card">
                        <div class="ico">🧾</div>
                        <h4 class="h5 mt-2">Staff</h4>
                        <p>Manage customers, orders, payments, inventory, and delivery assignments.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="px-card">
                        <div class="ico">🚚</div>
                        <h4 class="h5 mt-2">Delivery team</h4>
                        <p>See assigned runs, update delivery status, and record returned containers.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="px-card">
                        <div class="ico">📊</div>
                        <h4 class="h5 mt-2">Administrator</h4>
                        <p>Manage users, monitor transactions, and generate sales and inventory reports.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container">
            <h2 class="mb-4">Highlights</h2>
            <div id="slides" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button data-bs-target="#slides" data-bs-slide-to="0" class="active" aria-label="Slide 1"></button>
                    <button data-bs-target="#slides" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button data-bs-target="#slides" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="slide s1">
                            <div>
                                <h3>Container tracking</h3>
                                <p>Every 5-gallon container is marked available, with a customer, out for delivery, or returned.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="slide s2">
                            <div>
                                <h3>Pickup or delivery</h3>
                                <p>Order ahead and collect at the station, or have it delivered across Marikina.</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="slide s3">
                            <div>
                                <h3>Records</h3>
                                <p>No more lost receipts. Payments and stock update with each transaction.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" data-bs-target="#slides" data-bs-slide="prev" aria-label="Previous"><span class="carousel-control-prev-icon"></span></button>
                <button class="carousel-control-next" data-bs-target="#slides" data-bs-slide="next" aria-label="Next"><span class="carousel-control-next-icon"></span></button>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-6">
                    <h2 class="mb-3">See the station</h2>
                    <div class="tiktok-landscape-wrapper border border-3 border-dark">
                        <iframe
                            id="tiktok-player"
                            src="https://www.tiktok.com/player/v1/7313952032467979566?autoplay=0&amp;music_info=0&amp;description=0"
                            allow="fullscreen"
                            title="AquaTrack TikTok Video">
                        </iframe>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h2 class="mb-3">Delivery calendar</h2>
                    <div id="calendar" class="cal"></div>
                    <ul id="sched" class="list-group rounded-0 mt-2"></ul>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
