document.addEventListener("DOMContentLoaded", function () {
  const buyBtn = document.getElementById("affilixwp-buy-btn");
  const statusEl = document.getElementById("affilixwp-status");

  if (!buyBtn || !statusEl) return;

  buyBtn.addEventListener("click", async function () {
    if (!window.AffilixWP || !AffilixWP.wp_user_id) {
      alert("Please log in to continue.");
      return;
    }

    // Prevent double clicks
    buyBtn.disabled = true;
    statusEl.innerText = "Creating subscription...";

    try {
      // 1️⃣ Create Razorpay subscription (server-side)
      const res = await fetch(
        `${AffilixWP.api_url}/razorpay/create-subscription`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            planId: "plan_Rz1Wf5QAFCxCOA", // 🔁 your real plan ID
            wpUserId: AffilixWP.wp_user_id,
          }),
        }
      );

      if (!res.ok) {
        throw new Error("Failed to create subscription");
      }

      const subscription = await res.json();

      // 2️⃣ Open Razorpay Checkout
      const options = {
        key: AffilixWP.razorpay_key, // public key
        subscription_id: subscription.id,
        name: "AffilixWP",
        description: "AffilixWP Subscription",

        handler: function () {
          // ✅ DO NOT WAIT FOR WEBHOOK
          statusEl.innerText = "Payment successful! Redirecting…";

          setTimeout(() => {
            window.location.href = "/thank-you/";
          }, 1500);
        },

        modal: {
          ondismiss: function () {
            buyBtn.disabled = false;
            statusEl.innerText = "Payment cancelled.";
          },
        },

        theme: {
          color: "#4F46E5",
        },
      };

      const rzp = new Razorpay(options);
      rzp.open();

    } catch (err) {
      console.error(err);
      buyBtn.disabled = false;
      statusEl.innerText = "Checkout failed.";
      alert("Something went wrong. Please try again.");
    }
  });
});
