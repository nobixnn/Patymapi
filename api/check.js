import fs from "fs";
import path from "path";

export default async function handler(req, res) {
    const { mid, oid } = req.query;

    if (!mid) {
        return res.status(400).json({ status: "error", message: "mid parameter missing" });
    }

    // Read allowed_mids.txt
    const filePath = path.join(process.cwd(), "allowed_mids.txt");
    let allowedMids = [];

    try {
        const data = fs.readFileSync(filePath, "utf8");
        allowedMids = data.split("\n").map(s => s.trim()).filter(Boolean);
    } catch (e) {
        return res.status(500).json({ status: "error", message: "File not found: allowed_mids.txt" });
    }

    // Check MID exists
    if (!allowedMids.includes(mid)) {
        return res.status(200).json({
            status: "not_allowed",
            message: "Apuni api buy karne ke liye @tushar ko dm kare"
        });
    }

    // MID exists → Call external API
    const apiUrl = `https://king.thesmmpanel.shop/api/aadhar-info/check?mid=${mid}&key=${mid}&oid=${oid || ""}`;

    try {
        const response = await fetch(apiUrl);
        const text = await response.text();

        let json;
        try {
            json = JSON.parse(text);
            return res.status(response.status).json(json);
        } catch {
            return res.status(response.status).send(text); // non-JSON fallback
        }
    } catch (error) {
        return res.status(500).json({
            status: "error",
            message: "Upstream API error",
            detail: error.message
        });
    }
}
