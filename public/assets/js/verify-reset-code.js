const verifyForm = document.getElementById("verifyResetCodeForm");
const verifyMessage = document.getElementById("verifyResetCodeMessage");
const sendResetCodeBtn = document.getElementById("sendResetCodeBtn");

const setVerifyMessage = (text, type = "") => {
    if (!verifyMessage) return;
    verifyMessage.textContent = text;
    verifyMessage.classList.remove("is-error", "is-success");
    if (type) {
        verifyMessage.classList.add(type);
    }
};

if (verifyForm) {
    const urlEmail = new URLSearchParams(window.location.search).get("email") || "";
    if (urlEmail && verifyForm.email instanceof HTMLInputElement) {
        verifyForm.email.value = urlEmail;
    }

    const sendCode = async () => {
        if (!(verifyForm.email instanceof HTMLInputElement)) return;

        const email = verifyForm.email.value.trim();
        if (!email) {
            setVerifyMessage("Email obligatoire.", "is-error");
            return;
        }

        if (sendResetCodeBtn instanceof HTMLButtonElement) {
            sendResetCodeBtn.disabled = true;
            sendResetCodeBtn.textContent = "Envoi...";
        }
        setVerifyMessage("");

        try {
            const response = await fetch("/api/auth/forgot", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                setVerifyMessage(result.error || "Erreur serveur.", "is-error");
                return;
            }

            setVerifyMessage(result.message || "Code envoye par email.", "is-success");
        } catch {
            setVerifyMessage("Connexion serveur impossible.", "is-error");
        } finally {
            if (sendResetCodeBtn instanceof HTMLButtonElement) {
                sendResetCodeBtn.disabled = false;
                sendResetCodeBtn.textContent = "Envoyer le code";
            }
        }
    };

    if (sendResetCodeBtn) {
        sendResetCodeBtn.addEventListener("click", sendCode);
    }

    verifyForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const button = verifyForm.querySelector("button[type='submit']");
        if (!(button instanceof HTMLButtonElement)) return;

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = "Verification...";
        setVerifyMessage("");

        try {
            const response = await fetch("/api/auth/verify-reset-code", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: verifyForm.email.value.trim(),
                    code: verifyForm.code.value.trim(),
                }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                setVerifyMessage(result.error || "Erreur serveur.", "is-error");
                return;
            }

            setVerifyMessage(result.message || "Code valide.", "is-success");
            if (result.reset_url) {
                window.location.href = result.reset_url;
            }
        } catch {
            setVerifyMessage("Connexion serveur impossible.", "is-error");
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
}
