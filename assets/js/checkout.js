document.addEventListener("DOMContentLoaded", function () {
  const buyBtn = document.getElementById("affilixwp-buy-btn");
  const statusEl = document.getElementById("affilixwp-status");
  if (!buyBtn) return;

  buyBtn.addEventListener("click", async function () {
    if (!window.AffilixWP || parseInt(AffilixWP.wp_user_id, 10) <= 0) {
      alert("Please log in to continue.");
      return;
    }

    if (statusEl) statusEl.innerText = "Creating subscription...";
    buyBtn.disabled = true;

    try {
      const res = await fetch(`${AffilixWP.api_url}/razorpay/create-subscription`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": AffilixWP.nonce
        },
        body: JSON.stringify({})
      });
      const data = await res.json();
      if (!res.ok || !data.id) {
        throw new Error(data.error || "Unable to start checkout. Please try again.");
      }

      const options = {
        key: AffilixWP.razorpay_key,
        subscription_id: data.id,
        name: "AffilixWP",
        description: "AffilixWP Subscription",
        handler: async function (response) {
          if (statusEl) statusEl.innerText = "Verifying payment...";
          try {
            const verifyRes = await fetch(`${AffilixWP.api_url}/razorpay/verify-payment`, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": AffilixWP.nonce
              },
              body: JSON.stringify({
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_subscription_id: response.razorpay_subscription_id,
                razorpay_signature: response.razorpay_signature
              })
            });
            const verifyData = await verifyRes.json();
            if (!verifyRes.ok || !verifyData.success) {
              throw new Error(verifyData.error || "Payment verification failed.");
            }
            if (statusEl) statusEl.innerText = "Payment verified. Subscription activation is being confirmed.";
          } catch (error) {
            console.error("AffilixWP payment verification error:", error);
            if (statusEl) statusEl.innerText = "Verification failed. Please contact support.";
            alert(error.message || "Payment verification failed.");
          } finally {
            buyBtn.disabled = false;
          }
        },
        modal: {
          ondismiss: function () {
            buyBtn.disabled = false;
            if (statusEl) statusEl.innerText = "Checkout closed.";
          }
        },
        theme: { color: "#4F46E5" }
      };

      const checkout = new Razorpay(options);
      checkout.on("payment.failed", function (response) {
        buyBtn.disabled = false;
        if (statusEl) statusEl.innerText = "Payment failed. Please try again.";
        console.error("AffilixWP Razorpay payment failed:", response.error);
      });
      checkout.open();
    } catch (error) {
      console.error("AffilixWP checkout error:", error);
      if (statusEl) statusEl.innerText = error.message || "Checkout failed.";
      alert(error.message || "Checkout failed.");
      buyBtn.disabled = false;
    }
  });
});
