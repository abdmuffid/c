import asyncio
import streamlit as st
import os
from docx import Document
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer

def get_or_create_eventloop():
    try:
        return asyncio.get_event_loop()
    except RuntimeError as ex:
        if "There is no current event loop in thread" in str(ex):
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)
            return asyncio.get_event_loop()

loop = asyncio.new_event_loop()
asyncio.set_event_loop(loop)

path_root = r"F:/KULIAH/Semester 3/Text Mining/Tugas/Demo/Result"
path_source = r"F:/KULIAH/Semester 3/Text Mining/Tugas/Demo/Source"
path_tfidf = os.path.join(path_root, "TF-IDF Output")

def search_text_in_directory(query, path_tfidf, num_files_to_display):
    results = []
    for root, dirs, files in os.walk(path_root):
        if 'TF-IDF Output' in dirs:
            dir_tfidf = os.path.join(root, 'TF-IDF Output')
            for root_tf, dirs_tf, files_tf in os.walk(dir_tfidf):
                for file_tf in files_tf:
                    if file_tf.endswith('.txt'):
                        file_path = os.path.join(root_tf, file_tf)
                        with open(file_path, "r", encoding="utf-8") as file:
                            lines = file.readlines()
                            for line in lines:
                                parts = line.strip().split(": ")
                                if len(parts) == 2:  # Check if there are two parts
                                    keyword, tfidf = parts
                                    if query in keyword:
                                        results.append((file_tf, keyword, tfidf))
    # Sort results by TF-IDF value in descending order
    sorted_results = sorted(results, key=lambda x: x[2], reverse=True)

    return sorted_results[:num_files_to_display]

def read_docx(file):
    doc = Document(file)
    text = ""
    for paragraph in doc.paragraphs:
        text += paragraph.text + "\n"
    return text

def create_expander(file_path, docx_path_source):
    with st.expander(f'Isi dokumen: {docx_path_source}', expanded=False):
        docx_text = read_docx(docx_path_source)
        st.markdown(f"<p style='text-align:justify;'>{docx_text}</p>", unsafe_allow_html=True)

# Web Pencarian
st.title("TF-IDF Search Engine")

search_query = st.text_input("Cari teks:")
num_files_to_display = st.slider("Jumlah File yang Ditampilkan", 1, 10, 5)

if st.button("Cari"):
    if search_query:
        results = search_text_in_directory(search_query, path_tfidf, num_files_to_display)
        if results:
            st.success("Hasil Pencarian:")

            for i, result in enumerate(results, 1):  # Start index at 1
                file_path, keyword, tfidf = result
                st.subheader(f"Hasil {i}:")
                st.write(f"File: {file_path}\n")
                st.write(f"Keyword: {keyword}\n")
                st.write(f"TF-IDF: {tfidf}\n")

                file_path = file_path.replace("TF-IDF_", "")
                docx_path = file_path.replace('.txt', '.docx')
                split_folder = docx_path.split('_')
                split_folder = split_folder[0]
                docx_path_source = os.path.join(path_source, split_folder, docx_path)
                
                create_expander(file_path, docx_path_source)
        else:
            st.warning("Tidak ditemukan hasil yang sesuai.")
    else:
        st.warning("Masukkan teks untuk pencarian.")