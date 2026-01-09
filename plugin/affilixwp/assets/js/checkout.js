/**
 * AffilixWP Checkout Script
 * Loaded only for logged-in users
 */

document.addEventListener("DOMContentLoaded", function () {

  // 🔍 Debug – confirm data is coming from WordPress
  console.log("WP User ID:", AffilixWP.wp_user_id);
  console.log("API URL:", AffilixWP.api_url);

  // Example: Start subscription checkout
  window.startAffilixWPCheckout = async function () {

    if (!AffilixWP || !AffilixWP.wp_user_id) {
      alert("You must be logged in to purchase.");
      return;
    }

    try {
      const response = await fetch(
        `${AffilixWP.api_url}/razorpay/create-subscription`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            planId: "plan_xxxxx", // 🔁 replace with real Razorpay plan ID
            wpUserId: AffilixWP.wp_user_id, // ✅ THIS IS THE KEY PART
          }),
        }
      );

      if (!response.ok) {
        throw new Error("Failed to create subscription");
      }

      const subscription = await response.json();

      console.log("Subscription created:", subscription);

      // TODO: Open Razorpay Checkout here
      // This will use subscription.id

    } catch (error) {
      console.error("Checkout error:", error);
      alert("Something went wrong. Please try again.");
    }
  };
});
