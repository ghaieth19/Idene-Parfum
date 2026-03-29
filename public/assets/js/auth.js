const tabs = document.querySelectorAll(".tab");
const forms = document.querySelectorAll(".form");
const forgotToggle = document.getElementById("forgotToggle");
const backToLoginBtn = document.getElementById("backToLoginBtn");
const faceLoginBtn = document.getElementById("faceLoginBtn");
const faceStatus = document.getElementById("faceStatus");
const faceSignupOptIn = document.getElementById("faceSignupOptIn");
const faceSignupBtn = document.getElementById("faceSignupBtn");
const validateFaceSignupBtn = document.getElementById("validateFaceSignupBtn");
const faceSignupStatus = document.getElementById("faceSignupStatus");
const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");
const forgotForm = document.getElementById("forgotForm");
const forgotSubmitBtn = document.getElementById("forgotSubmitBtn");
const loginMessage = document.getElementById("loginMessage");
const signupMessage = document.getElementById("signupMessage");
const forgotMessage = document.getElementById("forgotMessage");
const inlineFaceVideo = document.getElementById("inlineFaceVideo");
const inlineFaceCanvas = document.getElementById("inlineFaceCanvas");
const faceModal = document.getElementById("faceModal");
const faceModalText = document.getElementById("faceModalText");
const faceVideo = document.getElementById("faceVideo");
const faceCanvas = document.getElementById("faceCanvas");
const captureFaceBtn = document.getElementById("captureFaceBtn");
const closeFaceModalBtn = document.getElementById("closeFaceModalBtn");

const API = {
    signin: "/api/auth/signin",
    signup: "/api/auth/signup",
    forgot: "/api/auth/forgot",
    facePending: "/api/auth/face/pending",
    faceLogin: "/api/auth/face/login",
};

let pendingFaceReady = false;
let currentFaceMode = null;
let currentFaceStream = null;
let inlineFaceStream = null;

const activate = (target) => {
    tabs.forEach((tab) => tab.classList.toggle("active", tab.dataset.target === target));
    forms.forEach((form) => form.classList.toggle("active", form.id === `${target}Form`));
};

const showForgot = () => {
    tabs.forEach((tab) => tab.classList.remove("active"));
    forms.forEach((form) => form.classList.toggle("active", form.id === "forgotForm"));
};

const setMessage = (el, text, type = "") => {
    if (!el) return;
    el.textContent = text;
    el.classList.remove("is-error", "is-success");
    if (type) {
        el.classList.add(type);
    }
};

const setHtmlMessage = (el, html, type = "") => {
    if (!el) return;
    el.innerHTML = html;
    el.classList.remove("is-error", "is-success");
    if (type) {
        el.classList.add(type);
    }
};

const setFaceStatus = (text, type = "") => setMessage(faceStatus, text, type);
const setFaceSignupStatus = (text, type = "") => setMessage(faceSignupStatus, text, type);

const explainCameraError = (error) => {
    const raw = typeof error?.message === "string" ? error.message : "";
    const name = typeof error?.name === "string" ? error.name : "";

    if (!window.isSecureContext) {
        return "La camera exige un contexte securise. Utilisez http://localhost ou https.";
    }

    if (name === "NotAllowedError" || name === "PermissionDeniedError") {
        return "L acces a la camera a ete refuse.";
    }

    if (name === "NotFoundError" || name === "DevicesNotFoundError") {
        return "Aucune camera disponible sur cet appareil.";
    }

    return raw || "Impossible d ouvrir la camera.";
};

const postJson = async (url, payload) => {
    const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(result.error || "Erreur serveur.");
    }

    return result;
};

const handleJsonSubmit = async (form, url, payload, messageEl, successText) => {
    const button = form.querySelector("button[type='submit']");
    if (!(button instanceof HTMLButtonElement)) return null;

    const original = button.textContent;
    button.textContent = "Traitement...";
    button.disabled = true;
    setMessage(messageEl, "");

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) {
            const errorText = [result.error, result.details].filter(Boolean).join(" ");
            setMessage(messageEl, errorText || "Erreur serveur.", "is-error");
            return null;
        }

        if (result.reset_url) {
            const baseMessage = successText || result.message || "Operation validee.";
            const verifyUrl = result.verify_code_url
                ? String(result.verify_code_url)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                : "";
            const resetCode = result.reset_code
                ? String(result.reset_code)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                : "";
            setHtmlMessage(
                messageEl,
                `${baseMessage}`
                + `${resetCode ? `<br>Code: <strong>${resetCode}</strong>` : ""}`
                + `${verifyUrl ? `<br><a href="${verifyUrl}" target="_blank" rel="noopener noreferrer">Ouvrir la page du code</a>` : ""}`,
                "is-success"
            );
            return result;
        }

        setMessage(messageEl, successText || result.message || "Operation validee.", "is-success");
        return result;
    } catch {
        setMessage(messageEl, "Connexion serveur impossible.", "is-error");
        return null;
    } finally {
        button.textContent = original;
        button.disabled = false;
    }
};

const stopStream = (stream) => {
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
    }
};

const stopInlineCamera = () => {
    stopStream(inlineFaceStream);
    inlineFaceStream = null;
    if (inlineFaceVideo) {
        inlineFaceVideo.srcObject = null;
    }
};

const stopModalCamera = () => {
    stopStream(currentFaceStream);
    currentFaceStream = null;
    if (faceVideo) {
        faceVideo.srcObject = null;
    }
};

const closeFaceModal = () => {
    stopModalCamera();
    currentFaceMode = null;
    if (faceModal) {
        faceModal.hidden = true;
    }
};

const openLoginFaceModal = async () => {
    currentFaceMode = "login";
    if (faceModalText) {
        faceModalText.textContent = "Cadrez votre visage puis capturez pour vous connecter.";
    }

    currentFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    if (faceVideo) {
        faceVideo.srcObject = currentFaceStream;
        await faceVideo.play();
    }

    if (faceModal) {
        faceModal.hidden = false;
    }
};

const openInlineSignupCamera = async () => {
    stopInlineCamera();
    inlineFaceStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    if (inlineFaceVideo) {
        inlineFaceVideo.srcObject = inlineFaceStream;
        await inlineFaceVideo.play();
    }
};

const buildFaceMatrixFromElements = async (videoEl, canvasEl) => {
    if (!(videoEl instanceof HTMLVideoElement) || !(canvasEl instanceof HTMLCanvasElement)) {
        throw new Error("Camera indisponible.");
    }

    const context = canvasEl.getContext("2d", { willReadFrequently: true });
    if (!context) {
        throw new Error("Canvas indisponible.");
    }

    const width = videoEl.videoWidth || 640;
    const height = videoEl.videoHeight || 480;
    canvasEl.width = width;
    canvasEl.height = height;
    context.drawImage(videoEl, 0, 0, width, height);

    let crop = {
        x: width * 0.25,
        y: height * 0.15,
        width: width * 0.5,
        height: height * 0.7,
    };

    if ("FaceDetector" in window) {
        try {
            const detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
            const faces = await detector.detect(canvasEl);
            if (faces.length > 0) {
                const box = faces[0].boundingBox;
                const size = Math.max(box.width, box.height) * 1.3;
                crop = {
                    x: Math.max(0, box.x + (box.width - size) / 2),
                    y: Math.max(0, box.y + (box.height - size) / 2),
                    width: Math.min(size, width),
                    height: Math.min(size, height),
                };
            }
        } catch {
            // fallback
        }
    }

    const matrixCanvas = document.createElement("canvas");
    matrixCanvas.width = 32;
    matrixCanvas.height = 32;
    const matrixContext = matrixCanvas.getContext("2d", { willReadFrequently: true });
    if (!matrixContext) {
        throw new Error("Canvas de matrice indisponible.");
    }

    matrixContext.drawImage(
        canvasEl,
        crop.x,
        crop.y,
        crop.width,
        crop.height,
        0,
        0,
        32,
        32
    );

    const { data } = matrixContext.getImageData(0, 0, 32, 32);
    const matrix = [];
    for (let index = 0; index < data.length; index += 4) {
        const red = data[index];
        const green = data[index + 1];
        const blue = data[index + 2];
        const grayscale = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
        matrix.push(Number(grayscale.toFixed(6)));
    }

    return matrix;
};

const getSignupPayload = () => ({
    last_name: signupForm.last_name.value.trim(),
    first_name: signupForm.first_name.value.trim(),
    shop_name: signupForm.shop_name.value.trim(),
    phone: signupForm.phone.value.trim(),
    location: signupForm.location.value.trim(),
    email: signupForm.email.value.trim(),
    password: signupForm.password.value,
});

const refreshFaceSignupStatus = () => {
    if (!faceSignupOptIn?.checked) {
        setFaceSignupStatus("Le compte sera cree sans matrice faciale.");
        return;
    }

    if (pendingFaceReady) {
        setFaceSignupStatus("Visage valide et enregistre. Vous pouvez creer le compte.", "is-success");
        return;
    }

    setFaceSignupStatus("Ouvrez la camera puis cliquez sur valider le visage.");
};

tabs.forEach((tab) => {
    tab.addEventListener("click", () => activate(tab.dataset.target));
});

if (backToLoginBtn) {
    backToLoginBtn.addEventListener("click", () => activate("login"));
}

if (closeFaceModalBtn) {
    closeFaceModalBtn.addEventListener("click", closeFaceModal);
}

if (faceSignupOptIn) {
    faceSignupOptIn.addEventListener("change", () => {
        if (!faceSignupOptIn.checked) {
            pendingFaceReady = false;
            stopInlineCamera();
        }
        refreshFaceSignupStatus();
    });
}

if (faceSignupBtn) {
    faceSignupBtn.addEventListener("click", async () => {
        try {
            await openInlineSignupCamera();
            setFaceSignupStatus("Camera ouverte. Cliquez sur valider le visage.", "is-success");
        } catch (error) {
            setFaceSignupStatus(explainCameraError(error), "is-error");
        }
    });
}

if (validateFaceSignupBtn) {
    validateFaceSignupBtn.addEventListener("click", async () => {
        validateFaceSignupBtn.disabled = true;

        try {
            const matrix = await buildFaceMatrixFromElements(inlineFaceVideo, inlineFaceCanvas);
            const result = await postJson(API.facePending, { matrix });
            pendingFaceReady = true;
            setFaceSignupStatus(result.message || "Visage valide et enregistre.", "is-success");
            stopInlineCamera();
        } catch (error) {
            pendingFaceReady = false;
            setFaceSignupStatus(error.message || "Validation du visage impossible.", "is-error");
        } finally {
            validateFaceSignupBtn.disabled = false;
        }
    });
}

if (faceLoginBtn) {
    faceLoginBtn.addEventListener("click", async () => {
        try {
            await openLoginFaceModal();
        } catch (error) {
            setFaceStatus(explainCameraError(error), "is-error");
        }
    });
}

if (captureFaceBtn) {
    captureFaceBtn.addEventListener("click", async () => {
        captureFaceBtn.disabled = true;

        try {
            const matrix = await buildFaceMatrixFromElements(faceVideo, faceCanvas);
            const result = await postJson(API.faceLogin, { matrix });
            setFaceStatus(result.message || "Connexion par visage validee.", "is-success");
            closeFaceModal();

            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } catch (error) {
            setFaceStatus(error.message || "Connexion par visage impossible.", "is-error");
        } finally {
            captureFaceBtn.disabled = false;
        }
    });
}

if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const result = await handleJsonSubmit(
            loginForm,
            API.signin,
            {
                email: loginForm.email.value.trim(),
                password: loginForm.password.value,
            },
            loginMessage,
            "Connexion validee."
        );

        if (result?.redirect) {
            window.location.href = result.redirect;
        }
    });
}

if (signupForm) {
    signupForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const wantsFace = !!(faceSignupOptIn && faceSignupOptIn.checked);
        if (wantsFace && !pendingFaceReady) {
            setFaceSignupStatus("Validez d'abord le visage dans le bloc camera.", "is-error");
            return;
        }

        const result = await handleJsonSubmit(
            signupForm,
            API.signup,
            getSignupPayload(),
            signupMessage,
            "Compte cree avec succes."
        );

        if (result?.redirect) {
            window.location.href = result.redirect;
        }
    });
}

if (forgotForm) {
    const submitForgotForm = async (event) => {
        event.preventDefault();

        const emailInput = forgotForm.querySelector("input[name='recovery_email']");
        const emailValue = emailInput instanceof HTMLInputElement ? emailInput.value.trim() : "";
        if (!emailValue) {
            setMessage(forgotMessage, "Email obligatoire.", "is-error");
            return;
        }

        await handleJsonSubmit(
            forgotForm,
            API.forgot,
            {
                email: emailValue,
            },
            forgotMessage
        );
    };

    forgotForm.addEventListener("submit", submitForgotForm);

    if (forgotSubmitBtn) {
        forgotSubmitBtn.addEventListener("click", submitForgotForm);
    }
}

refreshFaceSignupStatus();
setFaceStatus("Cliquez sur le bouton pour ouvrir la camera et vous connecter par visage.");
