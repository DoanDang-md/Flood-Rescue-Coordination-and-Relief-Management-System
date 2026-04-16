const express = require("express");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

let requests = []; 

// ===== POST =====
app.post("/requests", (req, res) => {
  const { name, phone, content, latitude, longitude } = req.body;

  console.log("DATA NHẬN:", req.body); // debug

  if (!phone || !content) {
    return res.status(400).json({ message: "Thiếu dữ liệu!" });
  }

  const newRequest = {
    name: name || "Ẩn danh",
    phone,
    content,
    latitude,
    longitude
  };

  requests.push(newRequest);

  res.json({ message: "OK" });
});

// ===== GET =====
app.get("/requests/:phone", (req, res) => {
  const phone = req.params.phone;
  const result = requests.filter(r => r.phone === phone);

  console.log("KẾT QUẢ:", result);

  res.json(result);
});

// ===== START =====
app.listen(3000, () => {
  console.log("Server chạy tại http://localhost:3000");
});