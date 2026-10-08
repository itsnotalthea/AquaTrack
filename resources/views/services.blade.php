<x-app-layout>
    <x-slot name="title">Services | AquaTrack</x-slot>

    <header class="hero py-5"><div class="container"><h1>Services</h1><p class="lead mb-0">Refills, new containers, and delivery from AquaTrack Water Station.</p></div></header>

    <section class="section"><div class="container"><div class="row g-4">
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">💧</div><h4 class="h5 mt-2">5-gallon refill</h4><p>Bring your container or swap one at pickup. ₱25 per gallon.</p></div></div>
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">🪣</div><h4 class="h5 mt-2">New container</h4><p>Buy a new reusable container, recorded in container tracking. ₱250 each.</p></div></div>
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">🚚</div><h4 class="h5 mt-2">Home delivery</h4><p>Delivered within Marikina City. ₱30 delivery fee per order.</p></div></div>
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">🚰</div><h4 class="h5 mt-2">Dispenser rental</h4><p>Table-top dispenser for homes and small offices. ₱150 per month.</p></div></div>
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">♻️</div><h4 class="h5 mt-2">Container returns</h4><p>Return containers at the station or hand them to our rider.</p></div></div>
    <div class="col-md-6 col-lg-4"><div class="px-card"><div class="ico">🏢</div><h4 class="h5 mt-2">Bulk orders</h4><p>Regular deliveries for offices, stores, and events.</p></div></div></div></div></section>

    <section class="section alt" id="order"><div class="container"><div class="row justify-content-center"><div class="col-lg-8"><h2 class="mb-4">Place an order</h2>
    <div id="orderMsg" class="alert alert-success rounded-0 d-none"></div>
    <form id="orderForm" class="row g-3 needs-validation px-card" novalidate>
    <div class="col-md-6"><label class="form-label" for="cname">Full name</label><input id="cname" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label" for="phone">Mobile number</label><input id="phone" class="form-control" type="tel" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX" required><div class="invalid-feedback">Use an 11-digit number starting with 09.</div></div>
    <div class="col-md-8"><label class="form-label" for="product">Product</label><select id="product" class="form-select"><option value="refill">5-gallon refill (₱25/gal)</option><option value="newcont">New container (₱250)</option><option value="dispenser">Dispenser rental (₱150)</option></select></div>
    <div class="col-md-4"><label class="form-label" for="qty">Quantity</label><input id="qty" class="form-control" type="number" min="1" max="5" value="1" required></div>
    <div class="col-12"><span class="form-label d-block">Order type</span><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="method" id="m1" value="delivery" checked><label class="form-check-label" for="m1">Delivery</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="method" id="m2" value="pickup"><label class="form-check-label" for="m2">Pickup</label></div></div>
    <div class="col-12"><label class="form-label" for="addr">Delivery address</label><input id="addr" class="form-control" placeholder="House no., street, barangay, Marikina City"><div class="invalid-feedback">Enter your delivery address.</div></div>
    <div class="col-md-6"><label class="form-label" for="date">Preferred date</label><input id="date" class="form-control" type="date" required></div>
    <div class="col-md-6"><label class="form-label" for="pay">Payment</label><select id="pay" class="form-select"><option>Cash on delivery / pickup</option><option>GCash</option><option>Maya</option></select></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="ret"><label class="form-check-label" for="ret">I have empty containers to return</label></div><div class="form-check"><input class="form-check-input" type="checkbox" id="rec"><label class="form-check-label" for="rec">Remind me for a weekly refill</label></div></div>
    <div class="col-12"><label class="form-label" for="notes">Notes</label><textarea id="notes" class="form-control" rows="2"></textarea></div>
    <div class="col-12"><div class="total-box">Total: <span id="total"></span></div></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="agree" required><label class="form-check-label" for="agree">The details above are correct</label></div></div>
    <div class="col-12"><button class="btn btn-aqua px-4" type="submit">Place order</button></div></form></div></div></div></section>
</x-app-layout>
