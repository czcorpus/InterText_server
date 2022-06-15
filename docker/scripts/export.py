#!/usr/bin/python3

import argparse
import subprocess
from pathlib import Path

parser = argparse.ArgumentParser(description="Export text alignment from Intertext")
parser.add_argument("text", type=str)

args = parser.parse_args()


def export_text_version(text, version, output_path):
    output = subprocess.run(
        f"docker-compose exec www cli/export tname {text} {version}".split(),
        capture_output=True,
    )

    text_version = output.stdout.decode("utf-8")
    output_fn = output_path / f"{text}.{version}.xml"
    output_fn.write_text(text_version)


def export_text_alignment(text, from_version, to_version, output_path):
    output = subprocess.run(
        f"docker-compose exec www cli/export aname {text} {from_version} {to_version}".split(),
        capture_output=True,
    )
    text_alignment = output.stdout.decode("utf-8")
    output_fn = output_path / f"{text}.{from_version}.{to_version}.alignment.xml"
    output_fn.write_text(text_alignment)


def export_text(text):
    output_dir = Path(text)
    output_dir.mkdir(exist_ok=True)

    export_text_version(text, version="bo", output_path=output_dir)
    export_text_version(text, version="en", output_path=output_dir)
    export_text_alignment(
        text, from_version="bo", to_version="en", output_path=output_dir
    )

    return output_dir


if __name__ == "__main__":
    saved_path = export_text(text=args.text)
    print("saved at: ", saved_path)

