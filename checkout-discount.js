/**
 * Checkout discount functionality
 * Handles real-time discount application and price updates
 */

document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const discountCodeInput = document.getElementById("discount_id")
  const applyDiscountBtn = document.getElementById("apply-discount")
  const subtotalElement = document.getElementById("subtotal")
  const shippingElement = document.getElementById("shipping")
  const discountElement = document.getElementById("discount")
  const discountRow = document.getElementById("discount-row")
  const totalElement = document.getElementById("total")
  const checkoutForm = document.getElementById("checkout-form")
  const fidelityDiscountAmount = document.getElementById("fidelity_discount_amount")
  const discountRadios = document.querySelectorAll('input[name="discount_type"]')

  // Extract numeric value from price string (e.g., "1,000 D.A" -> 1000)
  function extractPrice(priceString) {
    return Number.parseInt(priceString.replace(/[^0-9]/g, ""))
  }

  // Format price as "X,XXX D.A"
  function formatPrice(price) {
    return price.toLocaleString() + " D.A"
  }

  // Calculate and update total
  function updateTotal() {
    const subtotal = extractPrice(subtotalElement.textContent)
    const shipping = extractPrice(shippingElement.textContent)
    const discount = discountRow.style.display === "none" ? 0 : extractPrice(discountElement.textContent)
    const total = subtotal + shipping - discount
    totalElement.textContent = formatPrice(total)
  }

  // Reset discount values
  function resetDiscount() {
    discountRow.style.display = "none"
    discountElement.textContent = "-0 D.A"
    discountCodeInput.value = ""
    updateTotal()
  }

  // Show message function
  function showMessage(message, type = "success") {
    let messageContainer = document.getElementById("message-container")
    if (!messageContainer) {
      messageContainer = document.createElement("div")
      messageContainer.id = "message-container"
      messageContainer.style.position = "fixed"
      messageContainer.style.top = "20px"
      messageContainer.style.right = "20px"
      messageContainer.style.zIndex = "1000"
      document.body.appendChild(messageContainer)
    }

    const messageElement = document.createElement("div")
    messageElement.className = `alert alert-${type}`
    messageElement.style.padding = "10px 15px"
    messageElement.style.marginBottom = "10px"
    messageElement.style.borderRadius = "4px"
    messageElement.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)"

    if (type === "success") {
      messageElement.style.backgroundColor = "#4ade80"
      messageElement.style.color = "#fff"
    } else {
      messageElement.style.backgroundColor = "#ef4444"
      messageElement.style.color = "#fff"
    }

    messageElement.textContent = message
    messageContainer.appendChild(messageElement)

    setTimeout(() => {
      messageElement.style.opacity = "0"
      messageElement.style.transition = "opacity 0.5s"
      setTimeout(() => {
        messageContainer.removeChild(messageElement)
      }, 500)
    }, 3000)
  }

  // Apply discount via API
  function applyDiscountViaAPI(code) {
    applyDiscountBtn.disabled = true
    applyDiscountBtn.textContent = "Applying..."

    const subtotal = extractPrice(subtotalElement.textContent)
    const formData = new FormData()
    formData.append("code", code)
    formData.append("subtotal", subtotal)

    fetch("discount-api.php", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok")
        }
        return response.json()
      })
      .then((data) => {
        applyDiscountBtn.disabled = false
        applyDiscountBtn.textContent = "Apply"

        if (data.success) {
          discountElement.textContent = "-" + data.formatted_discount
          discountRow.style.display = "flex"

          let discountAmountInput = document.getElementById("discount_amount")
          if (!discountAmountInput) {
            discountAmountInput = document.createElement("input")
            discountAmountInput.type = "hidden"
            discountAmountInput.id = "discount_amount"
            discountAmountInput.name = "discount_amount"
            checkoutForm.appendChild(discountAmountInput)
          }
          discountAmountInput.value = data.discount_amount

          // Add discount_id to form if available
          if (data.discount_id) {
            let discountIdInput = document.getElementById("discount_id")
            if (!discountIdInput) {
              discountIdInput = document.createElement("input")
              discountIdInput.type = "hidden"
              discountIdInput.id = "discount_id"
              discountIdInput.name = "discount_id"
              checkoutForm.appendChild(discountIdInput)
            }
            discountIdInput.value = data.discount_id
          }

          updateTotal()
          showMessage("Discount applied successfully!", "success")
        } else {
          showMessage(data.message || "Error applying discount", "error")
          resetDiscount()
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        applyDiscountBtn.disabled = false
        applyDiscountBtn.textContent = "Apply"
        showMessage("An error occurred while applying the discount.", "error")
        resetDiscount()
      })
  }

  // Handle discount type radio button changes
  if (discountRadios) {
    discountRadios.forEach((radio) => {
      radio.addEventListener("change", function () {
        let discountAmountInput = document.getElementById("discount_amount")
        if (!discountAmountInput) {
          discountAmountInput = document.createElement("input")
          discountAmountInput.type = "hidden"
          discountAmountInput.id = "discount_amount"
          discountAmountInput.name = "discount_amount"
          checkoutForm.appendChild(discountAmountInput)
        }

        if (this.value === "fidelity" && fidelityDiscountAmount) {
          const fidelityAmount = fidelityDiscountAmount.value
          discountCodeInput.value = ""
          discountCodeInput.disabled = true
          applyDiscountBtn.disabled = true
          discountElement.textContent = "-" + formatPrice(fidelityAmount)
          discountRow.style.display = "flex"
          discountAmountInput.value = fidelityAmount

          // Clear discount_id if using fidelity discount
          const discountIdInput = document.getElementById("discount_id")
          if (discountIdInput) {
            discountIdInput.value = ""
          }

          updateTotal()
          showMessage("Fidelity discount applied!", "success")
        } else if (this.value === "code") {
          discountCodeInput.disabled = false
          applyDiscountBtn.disabled = false
          const code = this.getAttribute("data-code")
          if (code) {
            discountCodeInput.value = code
            applyDiscountViaAPI(code)
          } else {
            resetDiscount()
          }
        }
      })
    })
  }

  // Apply discount button click
  if (applyDiscountBtn) {
    applyDiscountBtn.addEventListener("click", () => {
      const code = discountCodeInput.value.trim()
      if (code) {
        // Select the code radio button if a manual code is entered
        const codeRadio = document.querySelector('input[name="discount_type"][value="code"]')
        if (codeRadio) {
          codeRadio.checked = true
        }
        applyDiscountViaAPI(code)
      } else {
        showMessage("Please enter a discount code", "error")
      }
    })
  }

  // Initialize total calculation
  updateTotal()
})
