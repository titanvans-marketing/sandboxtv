<section class="checkout-form-section">
  <h3>Additional Information</h3>

  <div class="cart-checkout-grid">
    <div>
      <label for="source">How did you hear about us?</label>
      <input id="source" name="source" type="text" placeholder="Google, referral, Instagram, etc." />
    </div>

    <div class="cart-checkout-grid__full">
      <label for="anythingElse">Anything Else</label>
      <textarea
        id="anythingElse"
        name="anythingElse"
        rows="5"
        placeholder="Anything else you'd like us to know about the service request, vehicle symptoms, or appointment?"
      ></textarea>
    </div>

    <div class="cart-checkout-grid__full">
      <label for="vehicleImages">Vehicle Images</label>
      <input
        id="vehicleImages"
        name="vehicleImages[]"
        type="file"
        accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
        multiple
      />
      <p class="checkout-help-text">
        Optional. You can upload photos of the vehicle, issue area, or related service concern.
      </p>
    </div>
  </div>
</section>