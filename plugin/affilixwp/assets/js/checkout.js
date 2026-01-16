document.addEventListener("DOMContentLoaded", function () {

  const buyBtn = document.getElementById("affilixwp-buy-btn");
  const statusEl = document.getElementById("affilixwp-status");

  if (!buyBtn) return;

  buyBtn.addEventListener("click", async function () {

    if (!window.AffilixWP || parseInt(AffilixWP.wp_user_id) <= 0) {
      alert("Please log in to continue.");
      return;
    }

    statusEl.innerText = "Creating subscription...";

    try {
      const res = await fetch(
        `${AffilixWP.api_url}/razorpay/create-subscription`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            planId: "plan_Rz1Wf5QAFCxCOA",
            wpUserId: AffilixWP.wp_user_id,
          }),
        }
      );

      const data = await res.json();
      console.log("Razorpay API response:", data);

      if (!data.id) {
        alert("Unable to start checkout. Please try again.");
        return;
      }

      const options = {
        key: AffilixWP.razorpay_key,
        subscription_id: data.id,
        name: "AffilixWP",
        description: "AffilixWP Subscription",
        handler: function (response) {
          console.log("Payment success", response);
          statusEl.innerText = "Payment successful!";
        },
        theme: { color: "#4F46E5" },
      };

      const rzp = new Razorpay(options);
      rzp.open();

    } catch (err) {
      console.error("Checkout error", err);
      alert("Checkout failed.");
    }
  });
});
